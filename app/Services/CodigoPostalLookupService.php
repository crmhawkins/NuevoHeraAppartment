<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-26] Resuelve el codigo postal y la provincia correcta a partir de
 * direccion + localidad usando OpenStreetMap Nominatim.
 *
 * Por que: el OCR del reverso del DNI a veces lee mal el CP (ej: "PO2 B"
 * en vez de "11207") o lo deja vacio. Tener el CP exacto es obligatorio
 * para enviar la reserva al MIR. Esto resuelve el caso al vuelo cuando
 * tenemos direccion + ciudad razonablemente legibles.
 *
 * Por que Nominatim:
 *  - Gratis, sin API key.
 *  - Rate limit 1 req/seg (con header User-Agent obligatorio).
 *  - Cobertura buena en Espana, devuelve postcode + provincia normalizada.
 *
 * Estrategia:
 *  1. Construye una query "<direccion>, <localidad>" y llama a Nominatim.
 *  2. Si no hay resultado, prueba sin numero de calle, sin acentos, etc.
 *  3. Cachea el resultado 30 dias por hash de la query (no abusar del API).
 *  4. Devuelve ['codigo_postal','provincia','localidad'] o null.
 */
class CodigoPostalLookupService
{
    private const ENDPOINT = 'https://nominatim.openstreetmap.org/search';
    private const TIMEOUT_S = 12;
    private const CACHE_DIAS = 30;

    public function buscar(?string $direccion, ?string $localidad, ?string $provincia = null, string $pais = 'Espana'): ?array
    {
        $direccion = trim((string) $direccion);
        $localidad = trim((string) $localidad);

        // [2026-04-26] REGLA CRITICA: sin localidad NO buscamos. Sin contexto
        // de ciudad, Nominatim devuelve cualquier match plausible en Espana
        // (ej: "C/ Polonia 32" -> Madrid centro), y el LLM rellena con el CP
        // de la primera ciudad que se le ocurre. Eso pone datos inventados
        // en BD, peor que tener el campo vacio. La reserva queda en el
        // resumen MIR diario y el admin la puede arreglar a mano.
        if ($localidad === '') {
            Log::info('[CpLookup] Saltado: sin localidad', [
                'direccion' => $direccion,
            ]);
            return null;
        }
        if ($direccion === '' && $localidad === '') return null;

        // [2026-04-26] Filtro extranjero: si la direccion contiene marcadores
        // de pais NO espanol, saltamos. El admin lo gestionara manualmente.
        // Casos cubiertos:
        //  - frances: "rue", "route", "avenue", "boulevard"
        //  - aleman: "strasse", "-strasse" (pegado al nombre tipo Bahnhofstrasse)
        //  - ingles: "street", "road", "drive", "Dr.", "Rd."
        //  - portugues: "rua", "avenida" (avenida tambien existe en es,
        //    asi que solo lo aceptamos como marcador si va en mayusculas
        //    iniciales aisladas).
        $patrones = [
            '/\b(rue|route|boulevard|chemin|impasse)\b/iu',
            '/strasse\b/iu',
            '/\b(street|road|drive|avenue|ave\.?|dr\.?|rd\.?)\b/iu',
            '/\brua\b/iu',
        ];
        foreach ($patrones as $p) {
            if (preg_match($p, $direccion)) {
                Log::info('[CpLookup] Saltado: direccion parece extranjera', [
                    'direccion' => $direccion,
                    'localidad' => $localidad,
                ]);
                return null;
            }
        }

        // [2026-04-26] Si la localidad NO esta en una lista mas o menos plausible
        // de Espana, ser conservador: solo buscamos. Nominatim ya filtra por
        // countrycodes=es, asi que si la localidad es "Zurich" o "Barrie"
        // no devolvera ningun resultado coherente -> mejor no llamarlo y
        // ahorrar una request.
        if (!$this->localidadParecePosibleEnEspana($localidad)) {
            Log::info('[CpLookup] Saltado: localidad no parece espanola', [
                'localidad' => $localidad,
            ]);
            return null;
        }

        // Lista de variantes a probar, en orden de mas especifica a menos.
        $variantes = $this->variantesQuery($direccion, $localidad, $provincia, $pais);

        // [2026-04-26] Variante adicional: corregir typos OCR via LLM antes
        // del lookup. El reverso del DNI sale en una zona muy comprimida y
        // suele introducir errores como "GUADALMESTI" en vez de "GUADALMESI".
        // Usamos gpt-oss:120b-cloud (texto, gratis cloud) para normalizar.
        $direccionLimpia = $this->corregirTyposOcr($direccion, $localidad);
        if ($direccionLimpia && $direccionLimpia !== $direccion) {
            $variantes[] = "$direccionLimpia, $localidad, $pais";
            if ($provincia) {
                $variantes[] = "$direccionLimpia, $localidad, $provincia, $pais";
            }
            // Mover la variante limpia al primer puesto: es la mas probable
            // de funcionar.
            $variantes = array_unique(array_merge(
                ["$direccionLimpia, $localidad, $pais"],
                $variantes
            ));
        }

        foreach ($variantes as $q) {
            $cacheKey = 'cplookup:' . md5($q);
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                if (is_array($cached) && !empty($cached) && $this->resultadoCoincideConLocalidad($cached, $localidad)) {
                    return $cached;
                }
                continue;
            }

            $resultado = $this->llamarNominatim($q);

            if ($resultado) {
                // Validar que la localidad devuelta coincide con la pedida.
                // Si pedimos "Algeciras" y Nominatim devuelve algo de Madrid
                // porque encontro una calle homonima alli, descartamos: es
                // mejor seguir con la siguiente variante o caer al LLM, que
                // al menos sabe acotar al municipio correcto.
                if (!$this->resultadoCoincideConLocalidad($resultado, $localidad)) {
                    Log::info('[CpLookup] Descartado por mismatch de localidad', [
                        'query' => $q,
                        'localidad_pedida' => $localidad,
                        'localidad_recibida' => $resultado['localidad'] ?? null,
                    ]);
                    Cache::put($cacheKey, [], now()->addHours(6));
                    continue;
                }
                Cache::put($cacheKey, $resultado, now()->addDays(self::CACHE_DIAS));
                Log::info('[CpLookup] Resuelto', ['query' => $q, 'cp' => $resultado['codigo_postal']]);
                return $resultado;
            }

            Cache::put($cacheKey, [], now()->addHours(6));
        }

        // [2026-04-26] Fallback final: si Nominatim no encuentra nada
        // (tipico cuando el OCR introdujo demasiados typos), preguntamos
        // directamente al LLM grande por el CP. Solo si tenemos localidad,
        // para que el LLM no se invente la ciudad. La regla de saltar sin
        // localidad ya esta aplicada arriba.
        $cpLLM = $this->preguntarCpAlLLM($direccion, $localidad, $provincia);
        if ($cpLLM) {
            Log::info('[CpLookup] CP resuelto por LLM (fallback)', [
                'cp' => $cpLLM,
                'direccion' => $direccion,
                'localidad' => $localidad,
            ]);
            return [
                'codigo_postal' => $cpLLM,
                'provincia' => null,
                'localidad' => $localidad,
                'pais' => 'Espana',
                'fuente' => 'llm_fallback',
            ];
        }

        Log::info('[CpLookup] No resuelto', ['direccion' => $direccion, 'localidad' => $localidad]);
        return null;
    }

    /**
     * Pregunta directamente al LLM cual es el CP de una direccion. Es menos
     * preciso que Nominatim (puede equivocarse de zona dentro del municipio)
     * pero suele acertar el CP "central" cuando el OCR esta muy roto.
     */
    private function preguntarCpAlLLM(string $direccion, string $localidad, ?string $provincia): ?string
    {
        $modelo = env('CP_LLM_MODEL', 'gpt-oss:120b-cloud');
        $base = rtrim(env('OLLAMA_URL', 'http://10.0.0.1:11434'), '/');

        $prompt = "Dame el codigo postal espanol (5 digitos) de esta direccion. "
            . "El texto puede tener errores OCR. Si la calle no la conoces exacta, "
            . "devuelve el CP central del municipio. Responde EXCLUSIVAMENTE con los 5 digitos, sin texto.\n\n"
            . "Direccion: {$direccion}\n"
            . "Localidad: {$localidad}" . ($provincia ? ", {$provincia}" : '') . ", Espana";

        try {
            $resp = Http::timeout(25)->post($base . '/api/chat', [
                'model' => $modelo,
                'stream' => false,
                'options' => ['temperature' => 0],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
        } catch (\Throwable $e) {
            return null;
        }

        if (!$resp->successful()) return null;

        $raw = trim((string) ($resp->json('message.content') ?? ''));
        // Extraer 5 digitos seguidos.
        if (preg_match('/\b(\d{5})\b/', $raw, $m)) {
            $cp = $m[1];
            return $this->cpEsValidoEspana($cp) ? $cp : null;
        }
        return null;
    }

    /**
     * Un CP espanol valido tiene 5 digitos y los dos primeros son el codigo
     * de provincia (01 a 52). 00xxx, 53xxx-99xxx no existen como CP postal
     * peninsular/insular espanol.
     */
    private function cpEsValidoEspana(string $cp): bool
    {
        if (!preg_match('/^\d{5}$/', $cp)) return false;
        $provNum = (int) substr($cp, 0, 2);
        return $provNum >= 1 && $provNum <= 52;
    }

    /**
     * Llama a un LLM de texto via Ollama Cloud para corregir typos del OCR
     * en una direccion espanola. Devuelve la direccion limpia o null si no
     * pudo. Cachea 30 dias por hash.
     */
    private function corregirTyposOcr(string $direccion, string $localidad): ?string
    {
        if ($direccion === '') return null;

        $cacheKey = 'cplookup:ocrfix:' . md5(strtolower($direccion . '|' . $localidad));
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached ?: null;

        $modelo = env('OCR_TEXT_FIX_MODEL', 'gpt-oss:120b-cloud');
        $base = rtrim(env('OLLAMA_URL', 'http://10.0.0.1:11434'), '/');

        $prompt = "Corrige los errores de OCR en esta direccion postal espanola. "
            . "Mantenes el formato (calle, numero) y solo arreglas typos obvios. "
            . "Si una palabra no parece existir como nombre real, prueba la mas cercana. "
            . "Responde SOLO con la direccion corregida, sin comillas ni texto adicional.\n\n"
            . "Direccion: {$direccion}\n"
            . "Localidad: {$localidad}, Espana\n\n"
            . "Direccion corregida:";

        try {
            $resp = Http::timeout(20)->post($base . '/api/chat', [
                'model' => $modelo,
                'stream' => false,
                'options' => ['temperature' => 0],
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
        } catch (\Throwable $e) {
            Log::warning('[CpLookup] Excepcion al corregir typos: ' . $e->getMessage());
            Cache::put($cacheKey, '', now()->addHours(6));
            return null;
        }

        if (!$resp->successful()) {
            Cache::put($cacheKey, '', now()->addHours(6));
            return null;
        }

        $raw = trim((string) ($resp->json('message.content') ?? ''));
        // Limpieza: quitar comillas, "Direccion:" residual, lineas extra.
        $raw = preg_replace('/^["\']+|["\']+$/u', '', $raw);
        $raw = preg_replace('/^direccion[\s:]*/i', '', $raw);
        $raw = trim(strtok($raw, "\n")); // primera linea
        if ($raw === '' || mb_strlen($raw) > 200) {
            Cache::put($cacheKey, '', now()->addHours(6));
            return null;
        }

        Cache::put($cacheKey, $raw, now()->addDays(self::CACHE_DIAS));
        Log::info('[CpLookup] Typos corregidos por LLM', [
            'antes' => $direccion,
            'despues' => $raw,
        ]);
        return $raw;
    }

    /**
     * Genera variantes ordenadas de mas especifica a menos.
     */
    private function variantesQuery(string $direccion, string $localidad, ?string $provincia, string $pais): array
    {
        $base = [];
        if ($direccion !== '' && $localidad !== '') {
            $base[] = "$direccion, $localidad, $pais";
        }
        if ($direccion !== '' && $localidad !== '' && $provincia) {
            $base[] = "$direccion, $localidad, $provincia, $pais";
        }
        // Sin numero de calle (a veces los numeros confunden a Nominatim)
        $sinNumero = preg_replace('/\s+\d+[A-Z]?$/u', '', $direccion);
        if ($sinNumero !== '' && $sinNumero !== $direccion && $localidad !== '') {
            $base[] = "$sinNumero, $localidad, $pais";
        }
        // Solo localidad como ultimo recurso (al menos saca el CP medio)
        if ($localidad !== '') {
            $base[] = "$localidad, $pais";
        }

        // Deduplicar conservando orden
        return array_values(array_unique($base));
    }

    /**
     * Lista negra de palabras claramente NO espanolas en localidad. Si la
     * localidad incluye alguna de estas, ni intentamos.
     */
    private function localidadParecePosibleEnEspana(string $loc): bool
    {
        $locLower = mb_strtolower($loc, 'UTF-8');
        $marcadoresExtranjeros = [
            // Suiza/Alemania/Austria
            'zurich', 'zürich', 'bern', 'wien', 'munich', 'munchen', 'munchen',
            'berlin', 'hamburg', 'salzburg', 'innsbruck',
            // Francia
            'paris', 'lyon', 'marseille', 'bordeaux', 'occitanie', 'rhone',
            'normandie', 'aquitaine', 'provence', 'cote d',
            // UK/Irlanda
            'london', 'manchester', 'dublin', 'belfast', 'liverpool',
            // Italia
            'roma', 'milano', 'napoli', 'firenze', 'venezia', 'torino',
            // Portugal: faro y porto SI son tambien sitios de Galicia, asi
            // que NO los marco.
            // Resto Europa
            'amsterdam', 'rotterdam', 'bruxelles', 'brussel', 'praha',
            // America
            'mexico', 'buenos aires', 'lima', 'bogota', 'santiago de chile',
            'toronto', 'montreal', 'barrie', 'new york', 'los angeles',
            'miami', 'chicago',
            // Pais escrito directamente en la localidad
            'francia', 'france', 'germany', 'alemania', 'suiza', 'switzerland',
            'italy', 'italia', 'portugal', 'reino unido', 'united kingdom',
            'usa', 'united states', 'canada',
        ];
        foreach ($marcadoresExtranjeros as $m) {
            if (str_contains($locLower, $m)) return false;
        }
        return true;
    }

    /**
     * Comprueba que la localidad devuelta por Nominatim/LLM coincide (al menos
     * razonablemente) con la pedida. Tolerante a acentos y mayusculas.
     */
    private function resultadoCoincideConLocalidad(array $resultado, string $localidadPedida): bool
    {
        if ($localidadPedida === '') return true;
        $loc = (string) ($resultado['localidad'] ?? '');
        if ($loc === '') return false;
        return $this->normalizar($loc) === $this->normalizar($localidadPedida)
            || str_contains($this->normalizar($loc), $this->normalizar($localidadPedida))
            || str_contains($this->normalizar($localidadPedida), $this->normalizar($loc));
    }

    private function normalizar(string $s): string
    {
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/[^a-z0-9 ]+/u', '', strtr($s, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ç' => 'c',
        ]));
        return trim((string) $s);
    }

    /**
     * Llama a Nominatim con respeto del rate limit (1 req/s).
     */
    private function llamarNominatim(string $query): ?array
    {
        // Rate limit blando: lock de 1 seg para no superar el limite de Nominatim.
        $lockKey = 'cplookup:rate';
        $waitMs = 0;
        while (Cache::get($lockKey) && $waitMs < 5000) {
            usleep(150000); // 150 ms
            $waitMs += 150;
        }
        Cache::put($lockKey, true, now()->addSeconds(1));

        try {
            $resp = Http::withHeaders([
                'User-Agent' => env('NOMINATIM_USER_AGENT', 'HawkinsCRM/1.0 contact@hawkins.es'),
                'Accept-Language' => 'es',
            ])->timeout(self::TIMEOUT_S)
                ->get(self::ENDPOINT, [
                    'q' => $query,
                    'format' => 'json',
                    'addressdetails' => 1,
                    'limit' => 1,
                    'countrycodes' => 'es',
                ]);
        } catch (\Throwable $e) {
            Log::warning('[CpLookup] Excepcion HTTP: ' . $e->getMessage());
            return null;
        }

        if (!$resp->successful()) return null;

        $arr = $resp->json();
        if (!is_array($arr) || empty($arr[0])) return null;
        $r = $arr[0];
        $addr = $r['address'] ?? [];

        $cp = $addr['postcode'] ?? null;
        if (!$cp || !$this->cpEsValidoEspana((string) $cp)) {
            return null;
        }

        return [
            'codigo_postal' => (string) $cp,
            'provincia' => $addr['province'] ?? ($addr['state'] ?? null),
            'localidad' => $addr['city'] ?? ($addr['town'] ?? ($addr['village'] ?? null)),
            'pais' => $addr['country'] ?? null,
            'lat' => $r['lat'] ?? null,
            'lon' => $r['lon'] ?? null,
            'fuente' => 'nominatim',
        ];
    }
}
