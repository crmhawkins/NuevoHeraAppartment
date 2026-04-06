<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PhotoAnalysis;
use App\Models\Alert;
use App\Models\ApartamentoLimpieza;
use Illuminate\Support\Facades\Log;

class ResponsibilityController extends Controller
{
    public function markResponsibility(Request $request)
    {
        try {
            $request->validate([
                'limpieza_id' => 'required|integer|exists:apartamento_limpieza,id',
                'continuo_bajo_responsabilidad' => 'required|boolean'
            ]);

            $limpiezaId = $request->limpieza_id;
            $continuoBajoResponsabilidad = $request->continuo_bajo_responsabilidad;

            // Actualizar todos los análisis de esta limpieza
            $updated = PhotoAnalysis::where('limpieza_id', $limpiezaId)
                ->update(['continuo_bajo_responsabilidad' => $continuoBajoResponsabilidad]);

            if ($updated > 0) {
                // Si se marcó como bajo responsabilidad, crear alerta
                if ($continuoBajoResponsabilidad) {
                    $this->crearAlertaResponsabilidad($limpiezaId);
                }

                Log::info('Responsabilidad marcada', [
                    'limpieza_id' => $limpiezaId,
                    'continuo_bajo_responsabilidad' => $continuoBajoResponsabilidad,
                    'analisis_actualizados' => $updated
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Responsabilidad marcada correctamente',
                    'analisis_actualizados' => $updated
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron análisis para esta limpieza'
                ], 404);
            }

        } catch (\Exception $e) {
            Log::error('Error marcando responsabilidad: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Crear alerta cuando se acepta responsabilidad
     */
    private function crearAlertaResponsabilidad($limpiezaId)
    {
        try {
            // Obtener información de la limpieza
            $limpieza = ApartamentoLimpieza::with(['apartamento', 'empleada'])
                ->find($limpiezaId);

            if (!$limpieza) {
                Log::warning('No se pudo encontrar la limpieza para crear alerta', ['limpieza_id' => $limpiezaId]);
                return;
            }

            // Obtener análisis de esta limpieza para detalles
            $analisis = PhotoAnalysis::where('limpieza_id', $limpiezaId)->get();
            
            // Preparar mensaje de la alerta
            $apartamentoNombre = $limpieza->apartamento ? $limpieza->apartamento->nombre : 'Apartamento desconocido';
            $empleadaNombre = $limpieza->empleada ? $limpieza->empleada->name : 'Empleada desconocida';
            
            $mensaje = "⚠️ LIMPIEZA BAJO RESPONSABILIDAD\n\n";
            $mensaje .= "Apartamento: {$apartamentoNombre}\n";
            $mensaje .= "Empleada: {$empleadaNombre}\n";
            $mensaje .= "Fecha: " . now()->format('d/m/Y H:i') . "\n\n";
            
            // Añadir detalles de los análisis
            if ($analisis->count() > 0) {
                $mensaje .= "Detalles del análisis:\n";
                foreach ($analisis as $analisisItem) {
                    $mensaje .= "• {$analisisItem->categoria_nombre}: {$analisisItem->calidad_general} ({$analisisItem->puntuacion}/10)\n";
                    if (!empty($analisisItem->deficiencias)) {
                        $mensaje .= "  Deficiencias: " . implode(', ', $analisisItem->deficiencias) . "\n";
                    }
                }
            }

            // Crear la alerta
            Alert::create([
                'title' => 'Limpieza Bajo Responsabilidad',
                'content' => $mensaje,
                'type' => 'warning',
                'scenario' => 'cleaning_due',
                'user_id' => auth()->id(),
                'is_read' => false,
                'is_dismissible' => true,
                'action_url' => "/limpiezas/analisis",
                'action_text' => 'Ver Análisis'
            ]);

            Log::info('Alerta de responsabilidad creada', [
                'limpieza_id' => $limpiezaId,
                'apartamento' => $apartamentoNombre,
                'empleada' => $empleadaNombre
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando alerta de responsabilidad: ' . $e->getMessage());
        }
    }
}
