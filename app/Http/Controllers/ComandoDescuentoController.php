<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class ComandoDescuentoController extends Controller
{
    /**
     * Ejecutar comando de descuento desde la interfaz web
     */
    public function ejecutarComando(Request $request)
    {
        try {
            $comando = $request->input('comando');
            $tipo = $request->input('tipo');
            
            // Validar comando
            $comandosPermitidos = [
                'analizar:descuentos-temporada-baja',
                'aplicar:descuentos-channex',
                'ver:historial-descuentos'
            ];
            
            if (!in_array($comando, $comandosPermitidos)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comando no permitido'
                ], 400);
            }
            
            // Configurar output buffer para capturar la salida
            $output = new BufferedOutput();
            
            // Preparar parámetros según el comando
            $parametros = [];
            
            if ($tipo === 'analizar') {
                $parametros['--fecha'] = now()->format('Y-m-d');
            } elseif ($tipo === 'aplicar') {
                $parametros['--fecha'] = now()->format('Y-m-d');
                $parametros['--dry-run'] = true;
            }
            // Para 'historial' no se necesitan parámetros especiales
            
            // Ejecutar comando
            $exitCode = Artisan::call($comando, $parametros, $output);
            
            $outputContent = $output->fetch();
            
            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'output' => $outputContent,
                    'message' => 'Comando ejecutado exitosamente'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'output' => $outputContent,
                    'message' => 'Error ejecutando el comando'
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
