<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Services\BankinterScraperService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportarMovimientosBanco extends Command
{
    protected $signature = 'banco:importar-movimientos
                            {--cuenta= : Alias de la cuenta (ej: hawkins, helen). Si no se indica, importa TODAS las cuentas configuradas}
                            {--fecha-desde= : Fecha inicio YYYY-MM-DD (default: ultimo mes)}
                            {--fecha-hasta= : Fecha fin YYYY-MM-DD (default: hoy)}
                            {--solo-procesar= : Solo procesar un archivo existente (ruta)}
                            {--listar-cuentas : Muestra las cuentas configuradas y sale}
                            {--dry-run : Solo descargar sin importar}';

    protected $description = 'Descarga automatica de movimientos de Bankinter y los importa a la tesoreria del CRM. Soporta multiples cuentas bancarias.';

    private const MAX_REINTENTOS_DESCARGA = 3;
    private const PAUSA_ENTRE_REINTENTOS = 10; // segundos

    public function handle(): int
    {
        $service = new BankinterScraperService();

        // --- Listar cuentas ---
        if ($this->option('listar-cuentas')) {
            $cuentas = $service->listarCuentas();
            if (empty($cuentas)) {
                $this->warn('No hay cuentas Bankinter configuradas.');
                $this->line('Configura en config/services.php o en .env (BANKINTER_USER / BANKINTER_PASSWORD)');
                return 1;
            }
            $this->info('Cuentas Bankinter configuradas:');
            foreach ($cuentas as $alias) {
                $config = $service->obtenerConfigCuenta($alias);
                $iban = $config['iban'] ?? '(sin IBAN especifico)';
                $this->line("  - {$alias}: usuario={$config['user']}, IBAN={$iban}, bank_id={$config['bank_id']}");
            }
            return 0;
        }

        $this->info('[Bankinter] Inicio de importacion de movimientos - ' . now()->toDateTimeString());
        Log::info('[Bankinter] Comando banco:importar-movimientos iniciado');

        // --- Modo solo-procesar: skip descarga ---
        $soloProcesar = $this->option('solo-procesar');
        if ($soloProcesar) {
            $cuentaAlias = $this->option('cuenta') ?: 'default';
            $config = $service->obtenerConfigCuenta($cuentaAlias);
            $bankId = $config['bank_id'] ?? 1;

            // Crear backup antes de procesar
            try {
                $this->info("  Creando backup de tesoreria...");
                $backupFile = $service->crearBackupTesoreria();
                if ($backupFile) {
                    $this->info("  Backup creado: {$backupFile}");
                }
            } catch (\Exception $e) {
                $this->warn("  Error creando backup: {$e->getMessage()} (continuando igualmente)");
            }

            return $this->procesarArchivo($service, $soloProcesar, $bankId, $cuentaAlias);
        }

        // --- Determinar que cuentas procesar ---
        $cuentaAlias = $this->option('cuenta');
        if ($cuentaAlias) {
            // Una cuenta especifica
            $cuentas = [$cuentaAlias];
        } else {
            // Todas las configuradas
            $cuentas = $service->listarCuentas();
        }

        if (empty($cuentas)) {
            $this->error('No hay cuentas Bankinter configuradas.');
            return 1;
        }

        // --- Calcular fechas ---
        $fechaHasta = $this->option('fecha-hasta') ?: now()->format('Y-m-d');
        $fechaDesde = $this->option('fecha-desde') ?: now()->subMonth()->format('Y-m-d');

        $this->info("  Periodo: {$fechaDesde} a {$fechaHasta}");
        $this->info("  Cuentas a procesar: " . implode(', ', $cuentas));
        $this->info('');

        $exitCode = 0;

        // --- Procesar cada cuenta ---
        foreach ($cuentas as $cuenta) {
            $this->info("========================================");
            $this->info("  Cuenta: {$cuenta}");
            $this->info("========================================");

            $config = $service->obtenerConfigCuenta($cuenta);
            if (!$config) {
                $this->error("  Cuenta '{$cuenta}' no encontrada en la configuracion. Saltando...");
                $exitCode = 1;
                continue;
            }

            $bankId = $config['bank_id'] ?? 1;
            $resultado = $this->descargarConReintentos($service, $cuenta, $fechaDesde, $fechaHasta);

            if (!$resultado || !$resultado['success']) {
                $errorMsg = $resultado['error'] ?? 'Todos los reintentos fallaron';
                $this->error("  [{$cuenta}] ERROR descarga: {$errorMsg}");
                $this->enviarAlertaFallo($cuenta, "Descarga fallida: {$errorMsg}");
                $exitCode = 1;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->info("  [{$cuenta}] Dry-run: archivo descargado pero no procesado");
                continue;
            }

            // Crear backup de tesorería antes de importar
            try {
                $this->info("  [{$cuenta}] Creando backup de tesoreria...");
                $backupFile = $service->crearBackupTesoreria();
                if ($backupFile) {
                    $this->info("  [{$cuenta}] Backup creado: {$backupFile}");
                } else {
                    $this->warn("  [{$cuenta}] No se pudo crear backup (continuando igualmente)");
                }
            } catch (\Exception $e) {
                $this->warn("  [{$cuenta}] Error creando backup: {$e->getMessage()} (continuando igualmente)");
                Log::warning('[Bankinter] Error creando backup antes de importar', ['cuenta' => $cuenta, 'error' => $e->getMessage()]);
            }

            $resultProceso = $this->procesarArchivo($service, $resultado['file'], $bankId, $cuenta);
            if ($resultProceso !== 0) {
                $exitCode = 1;
            }

            $this->info('');
        }

        return $exitCode;
    }

    /**
     * Descargar con reintentos
     */
    private function descargarConReintentos(
        BankinterScraperService $service,
        string $cuenta,
        string $fechaDesde,
        string $fechaHasta
    ): ?array {
        $resultado = null;

        for ($intento = 1; $intento <= self::MAX_REINTENTOS_DESCARGA; $intento++) {
            $this->info("  [{$cuenta}] Intento de descarga {$intento}/" . self::MAX_REINTENTOS_DESCARGA . '...');

            $resultado = $service->descargarMovimientos($cuenta, $fechaDesde, $fechaHasta);

            if ($resultado['success'] && $resultado['file']) {
                $this->info("  [{$cuenta}] Descarga exitosa: {$resultado['file']}");
                return $resultado;
            }

            $this->warn("  [{$cuenta}] Intento {$intento} fallido: " . ($resultado['error'] ?? 'Error desconocido'));

            if ($intento < self::MAX_REINTENTOS_DESCARGA) {
                $this->info("  Esperando " . self::PAUSA_ENTRE_REINTENTOS . " segundos...");
                sleep(self::PAUSA_ENTRE_REINTENTOS);
            }
        }

        return $resultado;
    }

    /**
     * Procesar un archivo Excel y mostrar resultados
     */
    private function procesarArchivo(BankinterScraperService $service, string $filePath, int $bankId = 1, string $cuenta = 'default'): int
    {
        $this->info("  [{$cuenta}] Procesando: {$filePath}");

        $resumen = $service->procesarExcel($filePath, $bankId);

        if (!$resumen['success']) {
            $errorMsg = $resumen['error'] ?? 'Error al procesar Excel';
            $this->error("  [{$cuenta}] ERROR procesando: {$errorMsg}");
            Log::error('[Bankinter] Error procesando Excel', ['cuenta' => $cuenta, 'error' => $errorMsg, 'file' => $filePath]);
            $this->enviarAlertaFallo($cuenta, "Error procesando Excel: {$errorMsg}");
            return 1;
        }

        // Mostrar resumen
        $this->info('');
        $this->info("  [{$cuenta}] === RESUMEN IMPORTACION ===");
        $this->info("  Total filas:      {$resumen['total_filas']}");
        $this->info("  Procesados:       {$resumen['procesados']}");
        $this->info("  Duplicados:       {$resumen['duplicados']}");
        $this->info("  Errores:          {$resumen['errores']}");
        $this->info("  Ingresos creados: {$resumen['ingresos_creados']}");
        $this->info("  Gastos creados:   {$resumen['gastos_creados']}");
        $this->info("  Hashes huerfanos: {$resumen['hashes_huerfanos_eliminados']}");

        Log::info('[Bankinter] Importacion completada', array_merge($resumen, ['cuenta' => $cuenta]));

        // Notificacion interna si se importaron movimientos
        if ($resumen['procesados'] > 0) {
            try {
                Notification::createForAdmins(
                    Notification::TYPE_SISTEMA,
                    "Movimientos bancarios importados ({$cuenta})",
                    "Se han importado {$resumen['procesados']} movimientos de Bankinter [{$cuenta}] ({$resumen['ingresos_creados']} ingresos, {$resumen['gastos_creados']} gastos)",
                    [
                        'cuenta' => $cuenta,
                        'total_filas' => $resumen['total_filas'],
                        'procesados' => $resumen['procesados'],
                        'duplicados' => $resumen['duplicados'],
                    ],
                    Notification::PRIORITY_MEDIUM,
                    Notification::CATEGORY_SUCCESS
                );
            } catch (\Exception $e) {
                Log::warning('[Bankinter] No se pudo crear notificacion', ['error' => $e->getMessage()]);
            }
        }

        // Alerta si hay errores significativos
        if ($resumen['errores'] > 0 && $resumen['errores'] > $resumen['procesados'] * 0.1) {
            $this->enviarAlertaFallo(
                $cuenta,
                "Importacion con errores: {$resumen['errores']} errores de {$resumen['total_filas']} filas"
            );
        }

        return 0;
    }

    /**
     * Enviar alerta por WhatsApp y notificacion interna
     */
    private function enviarAlertaFallo(string $cuenta, string $mensaje): void
    {
        // WhatsApp
        try {
            $whatsapp = app(\App\Services\WhatsappNotificationService::class);
            $whatsapp->sendToConfiguredRecipients(
                "🔴 ALERTA BANKINTER [{$cuenta}]\n\n{$mensaje}\n\nFecha: " . now()->toDateTimeString()
            );
        } catch (\Exception $e) {
            Log::error('[Bankinter] No se pudo enviar alerta WhatsApp', ['error' => $e->getMessage()]);
        }

        // Notificacion interna
        try {
            Notification::createForAdmins(
                Notification::TYPE_SISTEMA,
                "Error importacion Bankinter ({$cuenta})",
                $mensaje,
                ['cuenta' => $cuenta, 'timestamp' => now()->toDateTimeString()],
                Notification::PRIORITY_HIGH,
                Notification::CATEGORY_ERROR
            );
        } catch (\Exception $e) {
            Log::error('[Bankinter] No se pudo crear notificacion de error', ['error' => $e->getMessage()]);
        }
    }
}
