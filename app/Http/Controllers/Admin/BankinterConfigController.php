<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankinterSyncLog;
use App\Services\BankinterScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class BankinterConfigController extends Controller
{
    /**
     * Pagina principal: lista de cuentas configuradas + estado de sincronizacion + historial.
     */
    public function index()
    {
        $service = new BankinterScraperService();
        $aliases = $service->listarCuentas();

        // Construir info de cada cuenta
        $cuentas = [];
        foreach ($aliases as $alias) {
            $config = $service->obtenerConfigCuenta($alias);
            $ultimoSync = BankinterSyncLog::where('cuenta_alias', $alias)
                ->orderByDesc('fecha_sync')
                ->first();

            $iban = $config['iban'] ?? '';
            $ibanMasked = $iban ? '****' . substr($iban, -4) : 'No configurado';

            $cuentas[] = [
                'alias'       => $alias,
                'iban'        => $ibanMasked,
                'bank_id'     => $config['bank_id'] ?? 1,
                'configurada' => !empty($config['user']) && !empty($config['password']),
                'ultimo_sync' => $ultimoSync,
            ];
        }

        // Historial reciente (ultimos 50 registros)
        $historial = BankinterSyncLog::orderByDesc('fecha_sync')
            ->limit(50)
            ->get();

        return view('admin.bankinter.index', compact('cuentas', 'historial'));
    }

    /**
     * Ejecutar sincronizacion para una cuenta especifica (via AJAX).
     */
    public function sincronizar(Request $request, string $cuenta)
    {
        try {
            $service = new BankinterScraperService();
            $config = $service->obtenerConfigCuenta($cuenta);

            if (!$config || empty($config['user'])) {
                return response()->json([
                    'success' => false,
                    'message' => "Cuenta '{$cuenta}' no configurada o sin credenciales.",
                ], 422);
            }

            // Crear registro de log con status 'running'
            $log = BankinterSyncLog::create([
                'cuenta_alias' => $cuenta,
                'fecha_sync'   => now(),
                'status'       => 'running',
            ]);

            // Ejecutar el comando de importacion
            $exitCode = Artisan::call('banco:importar-movimientos', [
                '--cuenta' => $cuenta,
            ]);

            $output = Artisan::output();

            // Parsear el output para extraer estadisticas
            $stats = $this->parsearOutputComando($output);

            $log->update([
                'total_filas'      => $stats['total_filas'],
                'procesados'       => $stats['procesados'],
                'duplicados'       => $stats['duplicados'],
                'errores'          => $stats['errores'],
                'ingresos_creados' => $stats['ingresos_creados'],
                'gastos_creados'   => $stats['gastos_creados'],
                'archivo'          => $stats['archivo'],
                'status'           => $exitCode === 0 ? 'success' : 'error',
                'error_message'    => $exitCode !== 0 ? $this->sanitizarOutput(substr($output, -500)) : null,
            ]);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0
                    ? "Sincronizacion completada: {$stats['procesados']} procesados, {$stats['duplicados']} duplicados"
                    : 'Error en la sincronizacion. Revisa el historial para mas detalles.',
                'stats'   => $stats,
                'log_id'  => $log->id,
            ]);

        } catch (\Exception $e) {
            Log::error('[BankinterConfig] Error en sincronizacion', [
                'cuenta' => $cuenta,
                'error'  => $e->getMessage(),
            ]);

            // Actualizar log si existe
            if (isset($log)) {
                $log->update([
                    'status'        => 'error',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ejecutar sincronizacion para TODAS las cuentas configuradas (via AJAX desde Diario de Caja).
     */
    public function sincronizarTodas(Request $request)
    {
        try {
            $service = new BankinterScraperService();
            $aliases = $service->listarCuentas();

            if (empty($aliases)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay cuentas Bankinter configuradas.',
                ], 422);
            }

            $resultados = [];
            $todoBien = true;

            foreach ($aliases as $alias) {
                $log = BankinterSyncLog::create([
                    'cuenta_alias' => $alias,
                    'fecha_sync'   => now(),
                    'status'       => 'running',
                ]);

                $exitCode = Artisan::call('banco:importar-movimientos', [
                    '--cuenta' => $alias,
                ]);

                $output = Artisan::output();
                $stats = $this->parsearOutputComando($output);

                $log->update([
                    'total_filas'      => $stats['total_filas'],
                    'procesados'       => $stats['procesados'],
                    'duplicados'       => $stats['duplicados'],
                    'errores'          => $stats['errores'],
                    'ingresos_creados' => $stats['ingresos_creados'],
                    'gastos_creados'   => $stats['gastos_creados'],
                    'archivo'          => $stats['archivo'],
                    'status'           => $exitCode === 0 ? 'success' : 'error',
                    'error_message'    => $exitCode !== 0 ? $this->sanitizarOutput(substr($output, -500)) : null,
                ]);

                if ($exitCode !== 0) $todoBien = false;

                $resultados[] = [
                    'cuenta' => $alias,
                    'success' => $exitCode === 0,
                    'stats' => $stats,
                ];
            }

            return response()->json([
                'success' => $todoBien,
                'message' => $todoBien
                    ? 'Sincronizacion completada para todas las cuentas.'
                    : 'Sincronizacion completada con errores en alguna cuenta.',
                'resultados' => $resultados,
            ]);

        } catch (\Exception $e) {
            Log::error('[BankinterConfig] Error en sincronizacion global', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Historial completo de sincronizaciones (paginado).
     */
    public function historial(Request $request)
    {
        $query = BankinterSyncLog::orderByDesc('fecha_sync');

        if ($request->filled('cuenta')) {
            $query->where('cuenta_alias', $request->input('cuenta'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $historial = $query->paginate(25);

        // Obtener cuentas para el filtro
        $service = new BankinterScraperService();
        $cuentasDisponibles = $service->listarCuentas();

        return view('admin.bankinter.historial', compact('historial', 'cuentasDisponibles'));
    }

    /**
     * Parsear la salida del comando artisan para extraer estadisticas.
     */
    private function parsearOutputComando(string $output): array
    {
        $stats = [
            'total_filas'      => 0,
            'procesados'       => 0,
            'duplicados'       => 0,
            'errores'          => 0,
            'ingresos_creados' => 0,
            'gastos_creados'   => 0,
            'archivo'          => null,
        ];

        if (preg_match('/Total filas:\s*(\d+)/i', $output, $m)) {
            $stats['total_filas'] = (int) $m[1];
        }
        if (preg_match('/Procesados:\s*(\d+)/i', $output, $m)) {
            $stats['procesados'] = (int) $m[1];
        }
        if (preg_match('/Duplicados:\s*(\d+)/i', $output, $m)) {
            $stats['duplicados'] = (int) $m[1];
        }
        if (preg_match('/Errores:\s*(\d+)/i', $output, $m)) {
            $stats['errores'] = (int) $m[1];
        }
        if (preg_match('/Ingresos creados:\s*(\d+)/i', $output, $m)) {
            $stats['ingresos_creados'] = (int) $m[1];
        }
        if (preg_match('/Gastos creados:\s*(\d+)/i', $output, $m)) {
            $stats['gastos_creados'] = (int) $m[1];
        }
        if (preg_match('/Descarga exitosa:\s*(.+)/i', $output, $m)) {
            $stats['archivo'] = trim($m[1]);
        }

        return $stats;
    }

    /**
     * Eliminar posibles datos sensibles del output antes de guardarlo.
     */
    private function sanitizarOutput(string $output): string
    {
        $patterns = [
            '/BANKINTER_USER=\S+/i'     => 'BANKINTER_USER=***',
            '/BANKINTER_PASSWORD=\S+/i'  => 'BANKINTER_PASSWORD=***',
            '/password["\s:=]+\S+/i'     => 'password=***',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $output);
    }
}
