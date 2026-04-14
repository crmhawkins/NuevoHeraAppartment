<?php

namespace App\Console\Commands;

use App\Models\FacturaPendiente;
use App\Services\FacturaScannerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Procesa las facturas pendientes:
 * 1) Escanea storage/app/facturas/pendientes/ buscando archivos nuevos
 *    que aun no esten en la tabla facturas_pendientes y los registra.
 * 2) Para cada registro en estado "pendiente" o "espera", llama al
 *    FacturaScannerService que hace la magia (IA + matching + mover).
 *
 * Se ejecuta cada 5 minutos via Kernel::schedule().
 */
class ProcesarFacturasPendientes extends Command
{
    protected $signature = 'facturas:procesar-pendientes {--force : Forzar incluso las que estan en espera hace poco}';

    protected $description = 'Procesa facturas pendientes: IA + emparejamiento automatico con gastos';

    public function handle(FacturaScannerService $service): int
    {
        $this->info('=== Procesando facturas pendientes ===');

        // Paso 1: escanear carpeta pendientes/ y registrar archivos nuevos
        $nuevas = $this->registrarNuevosArchivos();
        $this->info("Nuevos archivos registrados: {$nuevas}");

        // Paso 2: procesar pendientes + en espera
        $pendientes = FacturaPendiente::pendientes()->orderBy('id')->get();
        $this->info("Facturas a procesar: {$pendientes->count()}");

        $stats = [
            'asociadas' => 0,
            'espera' => 0,
            'error' => 0,
            'error_ia' => 0,
        ];

        foreach ($pendientes as $fp) {
            $this->line("  -> procesando id={$fp->id} file={$fp->filename}");
            try {
                $resultado = $service->procesarFactura($fp);
                $this->line("     resultado: {$resultado}");

                if ($resultado === 'asociada') {
                    $stats['asociadas']++;
                } elseif ($resultado === 'espera') {
                    $stats['espera']++;
                } elseif (str_starts_with($resultado, 'error_ia')) {
                    $stats['error_ia']++;
                } else {
                    $stats['error']++;
                }
            } catch (\Throwable $e) {
                $this->error("     excepcion: " . $e->getMessage());
                Log::error('[facturas:procesar-pendientes] excepcion', [
                    'factura_id' => $fp->id,
                    'error' => $e->getMessage(),
                ]);
                $fp->update([
                    'status' => 'error',
                    'error_message' => 'Excepcion: ' . $e->getMessage(),
                ]);
                $stats['error']++;
            }
        }

        $this->info('=== Resumen ===');
        foreach ($stats as $k => $v) {
            $this->line("  {$k}: {$v}");
        }

        return self::SUCCESS;
    }

    /**
     * Escanea storage/app/facturas/pendientes/ y registra archivos que todavia
     * no tengan una fila en facturas_pendientes.
     */
    private function registrarNuevosArchivos(): int
    {
        $dir = storage_path('app/facturas/pendientes');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
            return 0;
        }

        $entries = @scandir($dir);
        if (!$entries) return 0;

        $count = 0;
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') continue;
            $abs = $dir . DIRECTORY_SEPARATOR . $entry;
            if (!is_file($abs)) continue;

            // Saltar archivos ocultos/temporales
            if (str_starts_with($entry, '.') || str_starts_with($entry, '_')) continue;

            $relPath = 'facturas/pendientes/' . $entry;

            // Ya registrada?
            $exists = FacturaPendiente::where('storage_path', $relPath)->exists();
            if ($exists) continue;

            FacturaPendiente::create([
                'filename' => $entry,
                'storage_path' => $relPath,
                'size_bytes' => @filesize($abs) ?: null,
                'mime_type' => @mime_content_type($abs) ?: null,
                'status' => 'pendiente',
                'uploaded_from' => 'filesystem',
            ]);
            $count++;
        }

        return $count;
    }
}
