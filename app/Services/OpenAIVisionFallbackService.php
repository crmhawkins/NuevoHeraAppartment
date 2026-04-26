<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * [2026-04-26] Fallback para campos del DNI que el modelo local
 * qwen3-vl:8b extrae mal o vacios.
 *
 * Caso principal: numero_soporte_documento. Aparece en letras pequenas en la
 * esquina superior derecha del anverso del DNI espanol; el modelo de 8B local
 * frecuentemente no lo lee bien. La solucion es delegar SOLO ese campo, y
 * SOLO cuando falla la primera pasada, a un modelo mas grande.
 *
 * Modelo elegido: qwen3-vl:235b-cloud via Ollama Cloud (gratis con el plan
 * que ya tenemos para gpt-oss:120b-cloud). Mismo arquitecto que el local
 * pero 30x mas grande -> mucho mejor con texto pequeno. Sin coste extra,
 * sin claves de terceros, sin salida del ecosistema Ollama.
 *
 * El nombre de la clase se mantiene por compatibilidad con el resto del
 * codigo y para no romper inyecciones ya existentes.
 */
class OpenAIVisionFallbackService
{
    /** Regex canonico NUM SOPORTE DNI espanol moderno (3 letras + 6 digitos). */
    private const REGEX_DNI = '/^[A-Z]{3}\d{6}$/';

    /** Regex permisivo NUM SOPORTE: tolera 1-3 letras + 6-8 digitos (DNI viejo, NIE). */
    private const REGEX_PERMISIVO = '/^[A-Z]{1,3}\d{6,8}$/';

    /** Regex numero del documento DNI espanol (8 digitos + 1 letra). */
    private const REGEX_NUM_DNI = '/^\d{8}[A-Z]$/';

    /** Regex numero del documento NIE (X/Y/Z + 7 digitos + letra). */
    private const REGEX_NUM_NIE = '/^[XYZ]\d{7}[A-Z]$/';

    /**
     * Extrae unicamente el numero_soporte_documento de una imagen del anverso.
     * Devuelve el codigo en mayusculas si es valido, o null si no.
     *
     * [2026-04-26] Hace HASTA $maxIntentos llamadas al modelo si el resultado
     * es NULL/invalido. El modelo cloud es ligeramente no-determinista: hemos
     * verificado en produccion que casos que devuelven NULL en el primer
     * intento aciertan en el 2o o 3o. Aumenta la tasa de exito ~14% sin
     * coste extra significativo (cuando acierta a la primera, no reintenta).
     *
     * @param string $imagePath Ruta absoluta del fichero o ruta de Storage
     * @param int    $maxIntentos numero de llamadas hasta tener un resultado valido
     */
    public function extractNumeroSoporte(string $imagePath, int $maxIntentos = 3): ?string
    {
        $bytes = $this->cargarImagen($imagePath);
        if (!$bytes) {
            Log::warning('[OllamaCloudFallback] No se pudo cargar la imagen', [
                'path' => $imagePath,
            ]);
            return null;
        }

        $b64 = base64_encode($bytes);

        $prompt = <<<PROMPT
Eres un asistente de OCR de DNI espanol. Mira la imagen y devuelve UNICAMENTE
el codigo NUMERO DE SOPORTE (tambien etiquetado como NUM SOPORTE, IDESP, NSU
o SOPORTE).

Caracteristicas del codigo:
- Aparece en la parte SUPERIOR DERECHA del anverso, en letras pequenas.
- Formato canonico DNI moderno: 3 letras + 6 digitos (ej: BAB123456, AAA987654).
- Formato NIE: 1 letra + 8 digitos (ej: E01234567).
- NO es el numero del DNI (8 digitos + letra), NO es el codigo de barras,
  NO es el CAN (6 digitos solo), NO es el numero del MRZ.

Responde SOLO con el codigo en mayusculas, sin texto adicional, sin guiones,
sin espacios. Si no lo ves con claridad o no estas seguro al 100%, responde
exactamente "NULL".

Ejemplos de respuesta valida:
BAB123456
AAA987654
NULL
PROMPT;

        // Endpoint Ollama. Usamos el mismo wrapper que ya conoce el cluster:
        // pasa por el tunel reverso al 5090 y de ahi a Ollama, que para los
        // modelos *-cloud reenvia la peticion a ollama.com gratis.
        $base = rtrim(env('OLLAMA_URL', 'http://10.0.0.1:11434'), '/');
        $url = $base . '/api/chat';

        $modelo = env('FALLBACK_VISION_MODEL', 'qwen3-vl:235b-cloud');

        // [2026-04-26] Bucle de reintentos. Si la primera llamada devuelve un
        // codigo valido, salimos inmediatamente; solo gastamos llamadas extra
        // cuando hace falta. Variamos el seed implicitamente porque Ollama
        // no garantiza determinismo perfecto entre llamadas.
        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            $payload = [
                'model' => $modelo,
                'stream' => false,
                'options' => ['temperature' => $intento === 1 ? 0 : 0.2],
                'messages' => [[
                    'role' => 'user',
                    'content' => $prompt,
                    'images' => [$b64],
                ]],
            ];

            $t0 = microtime(true);
            try {
                $resp = Http::timeout(120)->post($url, $payload);
            } catch (\Throwable $e) {
                Log::error('[OllamaCloudFallback] Excepcion HTTP: ' . $e->getMessage(), [
                    'intento' => $intento,
                ]);
                continue;
            }
            $ms = (int) ((microtime(true) - $t0) * 1000);

            if (!$resp->successful()) {
                Log::warning('[OllamaCloudFallback] Ollama rechazo la peticion', [
                    'intento' => $intento,
                    'status' => $resp->status(),
                    'body' => mb_substr((string) $resp->body(), 0, 400),
                    'modelo' => $modelo,
                ]);
                continue;
            }

            $raw = trim((string) ($resp->json('message.content') ?? ''));
            $candidato = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $raw));

            $valido = preg_match(self::REGEX_DNI, $candidato)
                || preg_match(self::REGEX_PERMISIVO, $candidato);

            Log::info('[OllamaCloudFallback] Resultado soporte', [
                'modelo' => $modelo,
                'intento' => $intento,
                'raw' => $raw,
                'candidato' => $candidato,
                'valido' => (bool) $valido,
                'ms' => $ms,
            ]);

            if ($valido) return $candidato;
        }

        return null;
    }

    /**
     * Extrae el NUMERO de documento (DNI o NIE) de una foto del anverso.
     * Devuelve el codigo en mayusculas si valido, o null si no.
     *
     * Caso de uso: el modelo local de 8B a veces lee un digito de mas o
     * de menos (vimos "748533319A" cuando lo correcto era "74853319A").
     * El modelo grande cloud cuenta caracteres mucho mejor.
     */
    /**
     * @param string $tipo 'dni'|'nie'|'pasaporte'|'auto'
     */
    public function extractNumeroDocumento(string $imagePath, int $maxIntentos = 3, string $tipo = 'auto'): ?string
    {
        $bytes = $this->cargarImagen($imagePath);
        if (!$bytes) {
            Log::warning('[OllamaCloudFallback] No se pudo cargar la imagen para extractNumeroDocumento', [
                'path' => $imagePath,
            ]);
            return null;
        }

        $b64 = base64_encode($bytes);

        if ($tipo === 'pasaporte') {
            $prompt = <<<PROMPT
Eres OCR de pasaportes. Mira la imagen y devuelve UNICAMENTE el NUMERO de
pasaporte. Lo encuentras como "Pasaporte Nº" / "Passport No" / etc, en la
parte superior. NO devuelvas el MRZ entero (las 2 lineas con < < <).

Formato: alfanumerico, normalmente 6-9 caracteres (ej: AB123456, X12345678).

Responde SOLO con el codigo en mayusculas, sin espacios, sin guiones, sin
texto adicional. Si no lo ves claramente, responde "NULL".

Ejemplos validos:
AB123456
X12345678
PA0125487
NULL
PROMPT;
        } else {
            $prompt = <<<PROMPT
Eres OCR de documentos espanoles. Mira la imagen y devuelve UNICAMENTE el
NUMERO del documento (DNI o NIE).

Formatos validos:
- DNI: 8 digitos + 1 letra (ej: 12345678A, 74853319A). EXACTAMENTE 9 caracteres.
- NIE: X/Y/Z + 7 digitos + letra (ej: X1234567A, Y0123456B). EXACTAMENTE 9 caracteres.

Cuenta los digitos con cuidado. Si tienes dudas entre 8 y 9 digitos, mira
la longitud total: debe ser 9 caracteres EXACTOS.

Responde SOLO con el codigo en mayusculas, sin espacios, sin guiones, sin
ningun otro texto. Si no puedes leerlo con claridad, responde "NULL".

Ejemplos validos:
12345678A
74853319A
X1234567A
NULL
PROMPT;
        }

        $base = rtrim(env('OLLAMA_URL', 'http://10.0.0.1:11434'), '/');
        $url = $base . '/api/chat';
        $modelo = env('FALLBACK_VISION_MODEL', 'qwen3-vl:235b-cloud');

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            try {
                $resp = Http::timeout(120)->post($url, [
                    'model' => $modelo,
                    'stream' => false,
                    'options' => ['temperature' => $intento === 1 ? 0 : 0.2],
                    'messages' => [[
                        'role' => 'user',
                        'content' => $prompt,
                        'images' => [$b64],
                    ]],
                ]);
            } catch (\Throwable $e) {
                Log::error('[OllamaCloudFallback] Excepcion HTTP extractNumeroDocumento: ' . $e->getMessage(), [
                    'intento' => $intento,
                ]);
                continue;
            }

            if (!$resp->successful()) {
                Log::warning('[OllamaCloudFallback] Ollama rechazo extractNumeroDocumento', [
                    'intento' => $intento,
                    'status' => $resp->status(),
                    'body' => mb_substr((string) $resp->body(), 0, 400),
                ]);
                continue;
            }

            $raw = trim((string) ($resp->json('message.content') ?? ''));
            $candidato = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $raw));

            if ($tipo === 'pasaporte') {
                // Pasaporte: alfanumerico 6-15 chars, NO debe ser parte del MRZ
                // (que tiene >25 chars con <).
                $valido = preg_match('/^[A-Z0-9]{6,15}$/', $candidato);
            } else {
                $valido = preg_match(self::REGEX_NUM_DNI, $candidato)
                    || preg_match(self::REGEX_NUM_NIE, $candidato);
            }

            Log::info('[OllamaCloudFallback] extractNumeroDocumento resultado', [
                'tipo' => $tipo,
                'intento' => $intento,
                'raw' => $raw,
                'candidato' => $candidato,
                'valido' => (bool) $valido,
            ]);

            if ($valido) return $candidato;
        }

        return null;
    }

    /**
     * Carga los bytes de la imagen aceptando varias formas de path:
     *  - ruta absoluta del filesystem
     *  - ruta relativa de Storage
     *  - URL completa http(s)
     */
    private function cargarImagen(string $path): ?string
    {
        if (preg_match('#^https?://#i', $path)) {
            try {
                $r = Http::timeout(15)->get($path);
                return $r->successful() ? $r->body() : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        if (is_file($path)) {
            return file_get_contents($path) ?: null;
        }

        // El campo Photo::url a veces guarda 'private/photos/...' aunque el
        // fichero real este en 'storage/app/photos/...'. Probamos varios
        // candidatos antes de rendirnos.
        $clean = ltrim($path, '/');
        $sinPrivate = preg_replace('#^private/#', '', $clean);

        $candidatos = [
            storage_path('app/' . $clean),
            storage_path('app/' . $sinPrivate),
            storage_path('app/private/' . $sinPrivate),
            storage_path('app/public/' . $sinPrivate),
            base_path('public/storage/' . $sinPrivate),
            base_path('public/' . $sinPrivate),
        ];
        foreach ($candidatos as $c) {
            if (is_file($c)) {
                return file_get_contents($c) ?: null;
            }
        }

        foreach (['local', 'public', 'private'] as $disk) {
            try {
                if (Storage::disk($disk)->exists($clean)) {
                    return Storage::disk($disk)->get($clean);
                }
                if (Storage::disk($disk)->exists($sinPrivate)) {
                    return Storage::disk($disk)->get($sinPrivate);
                }
            } catch (\Throwable $e) { /* ignore */
            }
        }

        return null;
    }
}
