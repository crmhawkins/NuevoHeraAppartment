<?php

namespace App\Services;

use App\Models\FacturaPendiente;
use App\Models\Gastos;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * FacturaScannerService
 *
 * Motor de procesamiento de facturas pendientes:
 * 1) Envia la imagen a Hawkins AI (qwen2.5vl) para extraer datos.
 * 2) Busca un gasto existente que matchee por importe + fecha (+-15 dias).
 * 3) Si hay exactamente 1 candidato, mueve el archivo a facturas/procesadas/
 *    y actualiza gasto.factura_foto.
 * 4) Si hay 0 candidatos, deja la factura en "espera" (reintento en siguiente tick).
 * 5) Si hay 2+ candidatos, marca "error" con la lista para resolucion manual.
 * 6) Si >30 dias en "espera" sin match, pasa a "error".
 */
class FacturaScannerService
{
    private string $iaBaseUrl;
    private ?string $iaApiKey;
    private string $iaModel;
    private int $windowDays;
    private int $esperaMaxDias;

    public function __construct()
    {
        $this->iaBaseUrl     = rtrim(config('services.hawkins_ai.url', ''), '/');
        $this->iaApiKey      = config('services.hawkins_ai.api_key');
        $this->iaModel       = config('services.hawkins_ai.model', 'qwen2.5vl:latest');
        $this->windowDays    = (int) config('services.facturas.match_date_window_days', 15);
        $this->esperaMaxDias = (int) config('services.facturas.espera_max_dias', 30);
    }

    /**
     * Procesa una factura pendiente: llama a IA, busca candidatos, asocia si puede.
     */
    public function procesarFactura(FacturaPendiente $fp): string
    {
        $fp->update([
            'status' => 'procesando',
            'intentos' => $fp->intentos + 1,
            'last_attempt_at' => now(),
        ]);

        // Resolver ruta fisica absoluta del archivo
        $absolutePath = storage_path('app/' . ltrim($fp->storage_path, '/'));
        if (!file_exists($absolutePath)) {
            $fp->update([
                'status' => 'error',
                'error_message' => "Archivo no encontrado en disco: {$fp->storage_path}",
            ]);
            return 'error_archivo_no_encontrado';
        }

        // Paso 1: IA
        try {
            $datos = $this->extraerDatosDeImagen($absolutePath);
        } catch (\Throwable $e) {
            Log::error('[FacturaScanner] Error llamando a IA', [
                'id' => $fp->id,
                'error' => $e->getMessage(),
            ]);
            $fp->update([
                'status' => 'error',
                'error_message' => 'IA fallo: ' . $e->getMessage(),
            ]);
            return 'error_ia';
        }

        // Guardar datos extraidos
        $fp->update([
            'importe_detectado'        => $datos['importe_total'] ?? null,
            'fecha_detectada'          => $datos['fecha'] ?? null,
            'proveedor_detectado'      => $datos['proveedor'] ?? null,
            'numero_factura_detectado' => $datos['numero_factura'] ?? null,
            'concepto_detectado'       => $datos['concepto'] ?? null,
            'confianza_ia'             => $datos['confianza'] ?? null,
            'ia_raw_response'          => $datos['_raw'] ?? null,
        ]);

        // Validar que tenemos al menos importe y fecha
        if (empty($datos['importe_total']) || empty($datos['fecha'])) {
            $fp->update([
                'status' => 'error',
                'error_message' => 'IA no pudo extraer importe o fecha de la factura',
            ]);
            return 'error_datos_incompletos';
        }

        // Paso 2: buscar gastos candidatos
        $candidatos = $this->buscarGastosCandidatos(
            (float) $datos['importe_total'],
            Carbon::parse($datos['fecha'])
        );

        // Paso 3: decidir
        if ($candidatos->count() === 1) {
            $this->asociar($fp, $candidatos->first());
            return 'asociada';
        }

        if ($candidatos->count() === 0) {
            // Sin match: dejar en espera. Si lleva >espera_max_dias sin match, error.
            $diasDesdeUpload = $fp->created_at ? $fp->created_at->diffInDays(now()) : 0;
            if ($diasDesdeUpload >= $this->esperaMaxDias) {
                $fp->update([
                    'status' => 'error',
                    'error_message' => "Sin gasto candidato tras {$diasDesdeUpload} dias. Importe detectado: " . $datos['importe_total'] . "EUR",
                ]);
                $this->moverAErrorDir($fp);
                return 'error_sin_match_timeout';
            }
            $fp->update(['status' => 'espera']);
            return 'espera';
        }

        // 2+ candidatos: ambiguedad, marcar error con lista
        $ids = $candidatos->pluck('id')->toArray();
        $fp->update([
            'status' => 'error',
            'error_message' => 'Multiples gastos candidatos (' . implode(',', $ids) . '). Resolucion manual requerida.',
            'candidatos_gasto_ids' => $ids,
        ]);
        return 'error_ambiguo';
    }

    /**
     * Llama al endpoint de Hawkins AI (qwen2.5vl) y devuelve los datos estructurados.
     * Formato del request copiado de DNIScannerController::sendToAI().
     *
     * @return array{importe_total: float|null, fecha: string|null, proveedor: string|null, numero_factura: string|null, concepto: string|null, confianza: float|null, _raw: array}
     */
    public function extraerDatosDeImagen(string $imagePath): array
    {
        if (empty($this->iaBaseUrl) || empty($this->iaApiKey)) {
            throw new \RuntimeException('Hawkins AI no configurada (HAWKINS_AI_URL/HAWKINS_AI_API_KEY)');
        }

        if (!file_exists($imagePath)) {
            throw new \RuntimeException("Imagen no encontrada: {$imagePath}");
        }

        $endpoint = $this->iaBaseUrl . '/chat/analyze-image';

        $prompt = <<<'PROMPT'
Eres un OCR especializado en facturas y tickets. Analiza la imagen adjunta y extrae los datos.

Responde UNICAMENTE con un objeto JSON valido EXACTAMENTE en este formato, sin texto adicional, sin explicaciones, sin markdown:

{
"importe_total": 0.00,
"fecha": "YYYY-MM-DD",
"proveedor": "",
"numero_factura": "",
"concepto": "",
"confianza": 0.0
}

INSTRUCCIONES:
1. "importe_total": el TOTAL final de la factura (con IVA incluido), como numero sin simbolo de moneda ni separadores de miles. Usa punto como decimal. Ejemplo: 72.15
2. "fecha": la fecha de emision de la factura en formato YYYY-MM-DD. Si la factura tiene fecha de emision y fecha de vencimiento, usa la de emision.
3. "proveedor": el nombre del emisor de la factura (la empresa que cobra). Breve, sin razon social completa si es larga.
4. "numero_factura": el numero/codigo identificador de la factura. Si no se ve, usa cadena vacia.
5. "concepto": una descripcion corta del gasto (una frase). Si la factura tiene varias lineas, resumelas.
6. "confianza": numero entre 0.0 y 1.0 que refleje lo seguro que estas de los datos extraidos (1.0 = totalmente seguro, 0.0 = ilegible).

Si algun campo no se puede leer, usa cadena vacia para strings y 0 para numeros, pero intenta siempre sacar al menos el importe y la fecha.
PROMPT;

        // Copiado del patron cURL de DNIScannerController que ya funciona en produccion
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => [
                'image'  => new \CURLFile($imagePath, $this->detectarMime($imagePath), 'factura.jpg'),
                'prompt' => $prompt,
                'modelo' => $this->iaModel,
            ],
            CURLOPT_HTTPHEADER => [
                'X-API-Key: ' . $this->iaApiKey,
            ],
            CURLOPT_SSL_VERIFYPEER => app()->environment('production'),
            CURLOPT_SSL_VERIFYHOST => app()->environment('production') ? 2 : 0,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($curlError) {
            throw new \RuntimeException("cURL error: {$curlError}");
        }
        if ($httpCode !== 200) {
            throw new \RuntimeException("HTTP {$httpCode}: " . substr((string) $response, 0, 500));
        }

        $decoded = json_decode((string) $response, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException('Respuesta de IA no es JSON valido: ' . substr((string) $response, 0, 300));
        }

        // El endpoint aiapi devuelve formato variable; buscamos el JSON estructurado
        // dentro de distintos campos posibles.
        $payload = $this->localizarJsonFactura($decoded);
        if ($payload === null) {
            throw new \RuntimeException('No se encontro el JSON de factura en la respuesta IA: ' . json_encode($decoded));
        }

        // Sanear
        return [
            'importe_total'  => isset($payload['importe_total']) ? (float) $payload['importe_total'] : null,
            'fecha'          => $this->normalizarFecha($payload['fecha'] ?? null),
            'proveedor'      => isset($payload['proveedor'])     ? (string) $payload['proveedor']     : null,
            'numero_factura' => isset($payload['numero_factura'])? (string) $payload['numero_factura']: null,
            'concepto'       => isset($payload['concepto'])      ? (string) $payload['concepto']      : null,
            'confianza'      => isset($payload['confianza'])     ? (float)  $payload['confianza']     : null,
            '_raw'           => is_array($decoded) ? $decoded : ['_string' => (string) $response],
        ];
    }

    /**
     * Busca gastos candidatos por importe exacto y fecha dentro de la ventana +-windowDays.
     * Solo gastos sin factura_foto asignada.
     *
     * Nota: gastos.quantity puede estar almacenado en positivo o negativo historicamente,
     * pero tras el fix del importador Bankinter los nuevos son positivos. Comparamos
     * con ABS para cubrir ambos casos.
     */
    public function buscarGastosCandidatos(float $importe, Carbon $fecha)
    {
        $inicio = $fecha->copy()->subDays($this->windowDays)->format('Y-m-d');
        $fin    = $fecha->copy()->addDays($this->windowDays)->format('Y-m-d');

        return Gastos::query()
            ->whereBetween('date', [$inicio, $fin])
            ->where(function ($q) use ($importe) {
                $q->whereRaw('ABS(quantity) BETWEEN ? AND ?', [
                    $importe - 0.01,
                    $importe + 0.01,
                ]);
            })
            ->where(function ($q) {
                $q->whereNull('factura_foto')->orWhere('factura_foto', '');
            })
            ->get();
    }

    /**
     * Asocia una factura pendiente a un gasto: mueve el archivo a procesadas/
     * y actualiza la columna gasto.factura_foto.
     */
    public function asociar(FacturaPendiente $fp, Gastos $gasto): void
    {
        $ext = pathinfo($fp->filename, PATHINFO_EXTENSION) ?: 'jpg';
        $year = date('Y');
        $month = date('m');
        $destRelPath = "facturas/procesadas/{$year}/{$month}/{$gasto->id}_" . basename($fp->filename);
        // Si ya existe, anadir sufijo
        $destAbs = storage_path('app/' . $destRelPath);
        if (file_exists($destAbs)) {
            $destRelPath = "facturas/procesadas/{$year}/{$month}/{$gasto->id}_" . uniqid() . '.' . $ext;
            $destAbs = storage_path('app/' . $destRelPath);
        }
        if (!is_dir(dirname($destAbs))) {
            @mkdir(dirname($destAbs), 0755, true);
        }

        $srcAbs = storage_path('app/' . ltrim($fp->storage_path, '/'));
        if (!@rename($srcAbs, $destAbs)) {
            throw new \RuntimeException("No se pudo mover la factura {$srcAbs} -> {$destAbs}");
        }

        // Actualizar gasto con el nuevo path (compatible con como se almacena factura_foto)
        $gasto->factura_foto = $destRelPath;
        $gasto->save();

        $fp->update([
            'status' => 'asociada',
            'storage_path' => $destRelPath,
            'gasto_id' => $gasto->id,
            'resolved_at' => now(),
        ]);

        Log::info('[FacturaScanner] Factura asociada', [
            'factura_pendiente_id' => $fp->id,
            'gasto_id' => $gasto->id,
            'path' => $destRelPath,
        ]);
    }

    /**
     * Mueve una factura erronea a facturas/error/ dejando un sidecar .json con el motivo.
     */
    public function moverAErrorDir(FacturaPendiente $fp): void
    {
        $srcAbs = storage_path('app/' . ltrim($fp->storage_path, '/'));
        if (!file_exists($srcAbs)) return;

        $destRel = 'facturas/error/' . basename($fp->storage_path);
        $destAbs = storage_path('app/' . $destRel);
        if (!is_dir(dirname($destAbs))) {
            @mkdir(dirname($destAbs), 0755, true);
        }
        if (!@rename($srcAbs, $destAbs)) {
            Log::warning('[FacturaScanner] No se pudo mover a error/', ['src' => $srcAbs, 'dst' => $destAbs]);
            return;
        }

        // Sidecar con motivo
        $sidecar = $destAbs . '.json';
        @file_put_contents($sidecar, json_encode([
            'factura_pendiente_id' => $fp->id,
            'error_message' => $fp->error_message,
            'importe_detectado' => $fp->importe_detectado,
            'fecha_detectada' => $fp->fecha_detectada,
            'proveedor_detectado' => $fp->proveedor_detectado,
            'moved_at' => now()->toDateTimeString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $fp->update(['storage_path' => $destRel]);
    }

    // -------- helpers internos --------

    /**
     * Busca recursivamente un objeto con al menos importe_total + fecha en la respuesta.
     */
    private function localizarJsonFactura($data): ?array
    {
        if (!is_array($data)) return null;

        // Caso 1: el endpoint ya devuelve directamente el objeto factura
        if (array_key_exists('importe_total', $data) && array_key_exists('fecha', $data)) {
            return $data;
        }

        // Caso 2: envuelto en { data: {...} } o { respuesta: "<json string>" }
        if (isset($data['respuesta']) && is_string($data['respuesta'])) {
            // Intentar parsear sub-JSON
            $inner = json_decode($data['respuesta'], true);
            if (is_array($inner)) {
                if (array_key_exists('importe_total', $inner)) return $inner;
            }
            // O buscar un {...} dentro del string
            if (preg_match('/\{[\s\S]*\}/', $data['respuesta'], $m)) {
                $inner = json_decode($m[0], true);
                if (is_array($inner) && array_key_exists('importe_total', $inner)) return $inner;
            }
        }

        // Caso 3: recursion por las claves habituales
        foreach (['data', 'response', 'result', 'output'] as $k) {
            if (isset($data[$k]) && is_array($data[$k])) {
                $found = $this->localizarJsonFactura($data[$k]);
                if ($found) return $found;
            }
        }

        return null;
    }

    private function normalizarFecha(?string $f): ?string
    {
        if (!$f) return null;
        try {
            return Carbon::parse($f)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function detectarMime(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png'  => 'image/png',
            'jpg','jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'heic' => 'image/heic',
            'pdf'  => 'application/pdf',
            default => 'application/octet-stream',
        };
    }
}
