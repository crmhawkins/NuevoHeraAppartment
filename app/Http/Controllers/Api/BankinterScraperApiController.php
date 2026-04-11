<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankinterSyncLog;
use App\Services\AlertaEquipoService;
use App\Services\BankinterScraperService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BankinterScraperApiController
 *
 * Endpoint para recibir el archivo Excel exportado por el scraper Bankinter
 * que se ejecuta en un PC externo (Windows) y dispara la importacion al CRM.
 *
 * Configuracion requerida en .env:
 *     BANKINTER_SCRAPER_API_TOKEN=<token-aleatorio-largo>
 *
 * El token debe coincidir exactamente con el que envia el cliente externo en
 * la cabecera HTTP "X-Scraper-Token". La comparacion se realiza con
 * hash_equals() para evitar timing attacks.
 *
 * Endpoint: POST /api/bankinter/scraper/import
 * Throttle: 5 peticiones/minuto/IP (configurado en routes/api.php)
 */
class BankinterScraperApiController extends Controller
{
    private BankinterScraperService $scraperService;

    public function __construct(BankinterScraperService $scraperService)
    {
        $this->scraperService = $scraperService;
    }

    /**
     * Recibir el Excel del scraper externo y procesarlo.
     */
    public function import(Request $request): JsonResponse
    {
        $clientIp = $request->ip();

        // 1) Validacion del token (timing-safe)
        $expectedToken = config('services.bankinter.scraper_api_token');
        $providedToken = (string) $request->header('X-Scraper-Token', '');

        if (empty($expectedToken) || !is_string($expectedToken)) {
            Log::error('[BankinterScraperApi] Token no configurado en el servidor', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            Log::warning('[BankinterScraperApi] Token invalido', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2) Validacion de los campos
        $cuentas = config('services.bankinter.cuentas', []);
        $aliasesValidos = array_keys($cuentas);

        $validator = validator($request->all(), [
            'file' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'], // 10 MB
            'cuenta_alias' => ['required', 'string', 'in:' . implode(',', $aliasesValidos)],
        ]);

        if ($validator->fails()) {
            Log::warning('[BankinterScraperApi] Validacion fallida', [
                'ip' => $clientIp,
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors()->toArray(),
            ], 422);
        }

        $alias = $request->input('cuenta_alias');
        $cuentaConfig = $cuentas[$alias] ?? null;

        if (!$cuentaConfig || !isset($cuentaConfig['bank_id'])) {
            Log::error('[BankinterScraperApi] cuenta_alias sin bank_id', [
                'ip' => $clientIp,
                'alias' => $alias,
            ]);
            return response()->json([
                'error' => 'Validation failed',
                'details' => ['cuenta_alias' => ['No se pudo resolver bank_id para el alias indicado']],
            ], 422);
        }

        $bankId = (int) $cuentaConfig['bank_id'];

        // 3) Guardar el archivo en storage/app/bankinter/
        $storageDir = storage_path('app/bankinter');
        if (!is_dir($storageDir)) {
            @mkdir($storageDir, 0755, true);
        }

        $timestamp = now()->format('Ymd_His');
        $filename = "{$alias}_uploaded_{$timestamp}.xlsx";
        $destinationPath = $storageDir . DIRECTORY_SEPARATOR . $filename;

        try {
            $request->file('file')->move($storageDir, $filename);
        } catch (\Throwable $e) {
            Log::error('[BankinterScraperApi] Error guardando archivo', [
                'ip' => $clientIp,
                'alias' => $alias,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'error' => 'No se pudo guardar el archivo en el servidor',
            ], 500);
        }

        Log::info('[BankinterScraperApi] Archivo recibido', [
            'ip' => $clientIp,
            'alias' => $alias,
            'bank_id' => $bankId,
            'filename' => $filename,
        ]);

        // 4) Procesar el Excel via el servicio existente
        try {
            $resumen = $this->scraperService->procesarExcel($destinationPath, $bankId);
        } catch (\Throwable $e) {
            Log::error('[BankinterScraperApi] Excepcion procesando Excel', [
                'ip' => $clientIp,
                'alias' => $alias,
                'error' => $e->getMessage(),
            ]);
            AlertaEquipoService::scraperFallo($alias, $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Excepcion procesando el Excel: ' . $e->getMessage(),
            ], 500);
        }

        $success = (bool) ($resumen['success'] ?? false);
        $statusCode = $success ? 200 : 500;

        // Registrar en bankinter_sync_logs para que Diario de Caja muestre la última sincronización
        try {
            BankinterSyncLog::create([
                'cuenta_alias'     => $alias,
                'fecha_sync'       => now(),
                'status'           => $success ? 'success' : 'error',
                'total_filas'      => $resumen['total_filas'] ?? 0,
                'procesados'       => $resumen['procesados'] ?? 0,
                'duplicados'       => $resumen['duplicados'] ?? 0,
                'errores'          => $resumen['errores'] ?? 0,
                'ingresos_creados' => $resumen['ingresos_creados'] ?? 0,
                'gastos_creados'   => $resumen['gastos_creados'] ?? 0,
                'archivo'          => $filename,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[BankinterScraperApi] No se pudo registrar sync log', [
                'error' => $e->getMessage(),
            ]);
        }

        Log::info('[BankinterScraperApi] Importacion finalizada', [
            'ip' => $clientIp,
            'alias' => $alias,
            'bank_id' => $bankId,
            'success' => $success,
            'procesados' => $resumen['procesados'] ?? null,
            'duplicados' => $resumen['duplicados'] ?? null,
            'errores' => $resumen['errores'] ?? null,
        ]);

        return response()->json($resumen, $statusCode);
    }
}
