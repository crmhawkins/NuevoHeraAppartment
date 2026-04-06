<?php

namespace App\Http\Controllers;

use App\Models\Fichaje;
use App\Models\Pausa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FichajeController extends Controller
{
    public function iniciarJornada()
    {
        Log::info('FichajeController - Iniciando jornada para usuario: ' . Auth::id());
        
        try {
            $userId = Auth::id();
            $hoy = now()->toDateString();
            
            // VERIFICAR si ya hay una jornada activa
            $jornadaActiva = Fichaje::where('user_id', $userId)
                ->whereNull('hora_salida')
                ->first();
            
            if ($jornadaActiva) {
                Log::warning('FichajeController - Usuario ya tiene jornada activa ID: ' . $jornadaActiva->id);
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una jornada activa iniciada a las ' . \Carbon\Carbon::parse($jornadaActiva->hora_entrada)->format('H:i')
                ], 400);
            }
            
            // CALCULAR tiempo total trabajado hoy (antes de iniciar nueva)
            $tiempoTrabajadoHoy = $this->calcularTiempoTrabajadoHoy($userId);
            
            // CREAR nueva jornada solo si no hay una activa
            $fichaje = new Fichaje([
                'user_id' => $userId,
                'hora_entrada' => now(),
            ]);
            $fichaje->save();
            
            Log::info('FichajeController - Nueva jornada iniciada con ID: ' . $fichaje->id);
            
            // MENSAJE con tiempo trabajado hoy
            if ($tiempoTrabajadoHoy > 0) {
                $horas = intval($tiempoTrabajadoHoy / 60);
                $minutos = $tiempoTrabajadoHoy % 60;
                
                if ($horas > 0) {
                    $mensaje = "Jornada iniciada. Tiempo trabajado hoy: {$horas}h {$minutos}m";
                } else {
                    $mensaje = "Jornada iniciada. Tiempo trabajado hoy: {$minutos} minutos";
                }
            } else {
                $mensaje = 'Jornada iniciada exitosamente';
            }
            
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'fichaje_id' => $fichaje->id,
                'hora_inicio' => \Carbon\Carbon::parse($fichaje->hora_entrada)->format('H:i')
            ]);
            
        } catch (\Exception $e) {
            Log::error('FichajeController - Error iniciando jornada: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la jornada: ' . $e->getMessage()
            ], 500);
        }
    }

    public function iniciarPausa()
    {
        Log::info('FichajeController - Iniciando pausa para usuario: ' . Auth::id());
        
        try {
            $fichaje = Fichaje::where('user_id', Auth::id())
                ->whereNull('hora_salida')
                ->first();

            if (!$fichaje) {
                Log::warning('FichajeController - No se encontró jornada activa para usuario: ' . Auth::id());
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una jornada activa'
                ], 400);
            }

            $pausa = new Pausa([
                'fichaje_id' => $fichaje->id,
                'inicio_pausa' => now(),
            ]);
            $pausa->save();
            
            Log::info('FichajeController - Pausa iniciada con ID: ' . $pausa->id);
            return response()->json([
                'success' => true,
                'message' => 'Pausa iniciada',
                'pausa_id' => $pausa->id,
                'hora_inicio' => \Carbon\Carbon::parse($pausa->inicio_pausa)->format('H:i')
            ]);
            
        } catch (\Exception $e) {
            Log::error('FichajeController - Error iniciando pausa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la pausa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function finalizarPausa()
    {
        Log::info('FichajeController - Finalizando pausa para usuario: ' . Auth::id());
        
        try {
            $fichaje = Fichaje::where('user_id', Auth::id())
                ->whereNull('hora_salida')
                ->first();
                
            if (!$fichaje) {
                Log::warning('FichajeController - No se encontró jornada activa para usuario: ' . Auth::id());
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una jornada activa'
                ], 400);
            }
            
            $pausa = $fichaje->pausas()->whereNull('fin_pausa')->first();
            
            if (!$pausa) {
                Log::warning('FichajeController - No se encontró pausa activa para fichaje ID: ' . $fichaje->id);
                return response()->json([
                    'success' => false,
                    'message' => 'No hay pausa activa para finalizar'
                ], 400);
            }
            
            $pausa->fin_pausa = now();
            $pausa->save();
            
            Log::info('FichajeController - Pausa finalizada para fichaje ID: ' . $fichaje->id);
            return response()->json([
                'success' => true,
                'message' => 'Pausa finalizada',
                'pausa_id' => $pausa->id,
                'hora_fin' => \Carbon\Carbon::parse($pausa->fin_pausa)->format('H:i')
            ]);
            
        } catch (\Exception $e) {
            Log::error('FichajeController - Error finalizando pausa: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la pausa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function finalizarJornada()
    {
        Log::info('=== FINALIZAR JORNADA - MÉTODO EJECUTADO ===');
        Log::info('FichajeController - Finalizando jornada para usuario: ' . Auth::id());
        Log::info('Request method: ' . request()->method());
        Log::info('Request URL: ' . request()->url());
        Log::info('Request headers: ' . json_encode(request()->headers->all()));
        
        try {
            $userId = Auth::id();
            $hoy = now()->toDateString();
            
            // OBTENER todas las jornadas del usuario para HOY (con o sin hora_salida)
            $jornadasHoy = Fichaje::where('user_id', $userId)
                ->whereDate('hora_entrada', $hoy)
                ->get();
                
            Log::info('FichajeController - Jornadas encontradas para hoy: ' . $jornadasHoy->count());
            
            if ($jornadasHoy->isEmpty()) {
                Log::warning('FichajeController - No se encontraron jornadas para hoy para usuario: ' . $userId);
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una jornada para finalizar hoy'
                ], 400);
            }
            
            // CALCULAR tiempo total trabajado hoy
            $tiempoTotalTrabajado = 0;
            $jornadasFinalizadas = 0;
            
            foreach ($jornadasHoy as $jornada) {
                if ($jornada->hora_salida) {
                    // Jornada ya finalizada, calcular tiempo
                    $inicio = \Carbon\Carbon::parse($jornada->hora_entrada);
                    $fin = \Carbon\Carbon::parse($jornada->hora_salida);
                    $duracion = $inicio->diffInMinutes($fin);
                    $tiempoTotalTrabajado += $duracion;
                    
                    Log::info("FichajeController - Jornada ID {$jornada->id} ya finalizada, duración: {$duracion} minutos");
                } else {
                    // Jornada activa, finalizarla
                    Log::info('FichajeController - Finalizando jornada activa ID: ' . $jornada->id . ', hora_entrada: ' . $jornada->hora_entrada);
                    
                    $jornada->hora_salida = now();
                    $jornada->save();
                    
                    // Calcular tiempo de esta jornada
                    $inicio = \Carbon\Carbon::parse($jornada->hora_entrada);
                    $fin = \Carbon\Carbon::parse($jornada->hora_salida);
                    $duracion = $inicio->diffInMinutes($fin);
                    $tiempoTotalTrabajado += $duracion;
                    $jornadasFinalizadas++;
                    
                    Log::info("FichajeController - Jornada ID {$jornada->id} finalizada, duración: {$duracion} minutos");
                }
            }
            
            // CONVERTIR minutos a horas y minutos
            $horas = intval($tiempoTotalTrabajado / 60);
            $minutos = $tiempoTotalTrabajado % 60;
            
            Log::info('FichajeController - Total jornadas finalizadas: ' . $jornadasFinalizadas);
            Log::info('FichajeController - Tiempo total trabajado hoy: ' . $tiempoTotalTrabajado . ' minutos (' . $horas . 'h ' . $minutos . 'm)');
            
            // MENSAJE personalizado según el tiempo trabajado
            if ($horas > 0) {
                $mensaje = "Jornada finalizada. Tiempo total trabajado hoy: {$horas}h {$minutos}m";
            } else {
                $mensaje = "Jornada finalizada. Tiempo total trabajado hoy: {$minutos} minutos";
            }
            
            return response()->json([
                'success' => true,
                'message' => $mensaje,
                'tiempo_trabajado' => $tiempoTotalTrabajado,
                'horas' => $horas,
                'minutos' => $minutos,
                'jornadas_finalizadas' => $jornadasFinalizadas
            ]);
            
        } catch (\Exception $e) {
            Log::error('FichajeController - Error finalizando jornada: ' . $e->getMessage());
            Log::error('FichajeController - Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar la jornada: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calcular tiempo total trabajado hoy por un usuario
     */
    private function calcularTiempoTrabajadoHoy($userId)
    {
        $hoy = now()->toDateString();
        $tiempoTotal = 0;
        
        // Obtener todas las jornadas del usuario para hoy
        $jornadasHoy = Fichaje::where('user_id', $userId)
            ->whereDate('hora_entrada', $hoy)
            ->whereNotNull('hora_salida') // Solo jornadas finalizadas
            ->get();
            
        foreach ($jornadasHoy as $jornada) {
            $inicio = \Carbon\Carbon::parse($jornada->hora_entrada);
            $fin = \Carbon\Carbon::parse($jornada->hora_salida);
            $duracion = $inicio->diffInMinutes($fin);
            $tiempoTotal += $duracion;
        }
        
        Log::info("FichajeController - Tiempo total trabajado hoy para usuario {$userId}: {$tiempoTotal} minutos");
        
        return $tiempoTotal;
    }

    public function showControlPanel()
    {
        $hoy = now()->toDateString();
        $fichajeHoy = Fichaje::where('user_id', Auth::id())
                            ->whereDate('hora_entrada', $hoy)
                            ->latest()
                            ->first();

        $pausaActiva = null;
        if ($fichajeHoy && !$fichajeHoy->hora_salida) {
            $pausaActiva = $fichajeHoy->pausas()->whereNull('fin_pausa')->latest()->first();
        }

        return view('fichajes', compact('fichajeHoy', 'pausaActiva'));
    }

    /**
     * Obtener el estado actual del fichaje del usuario autenticado
     */
    public function estado()
    {
        try {
            $userId = Auth::id();
            $hoy = now()->toDateString();
            
            // Buscar fichaje activo (sin hora_salida) para hoy
            $fichajeActivo = Fichaje::where('user_id', $userId)
                ->whereDate('hora_entrada', $hoy)
                ->whereNull('hora_salida')
                ->first();
            
            // Si no hay fichaje para hoy, verificar si hay fichajes activos de días anteriores
            if (!$fichajeActivo) {
                $fichajeActivo = Fichaje::where('user_id', $userId)
                    ->whereNull('hora_salida')
                    ->where('hora_entrada', '<', now()->startOfDay())
                    ->first();
                
                // Si hay fichaje de día anterior, finalizarlo automáticamente
                if ($fichajeActivo) {
                    Log::info("FichajeController - Finalizando fichaje antiguo automáticamente ID: {$fichajeActivo->id}");
                    $horaEntrada = \Carbon\Carbon::parse($fichajeActivo->hora_entrada);
                    $fichajeActivo->hora_salida = $horaEntrada->copy()->addHours(8); // Asumir 8 horas
                    $fichajeActivo->save();
                    $fichajeActivo = null; // Ya no está activo
                }
            }
            
            if ($fichajeActivo) {
                // Verificar si hay pausa activa
                $pausaActiva = $fichajeActivo->pausas()
                    ->whereNull('fin_pausa')
                    ->latest()
                    ->first();
                
                return response()->json([
                    'fichaje_activo' => true,
                    'fichaje_id' => $fichajeActivo->id,
                    'hora_inicio' => \Carbon\Carbon::parse($fichajeActivo->hora_entrada)->format('H:i'),
                    'pausa_activa' => $pausaActiva ? true : false,
                    'pausa_id' => $pausaActiva ? $pausaActiva->id : null,
                    'hora_pausa' => $pausaActiva ? \Carbon\Carbon::parse($pausaActiva->inicio_pausa)->format('H:i') : null
                ]);
            } else {
                return response()->json([
                    'fichaje_activo' => false,
                    'fichaje_id' => null,
                    'hora_inicio' => null,
                    'pausa_activa' => false,
                    'pausa_id' => null,
                    'hora_pausa' => null
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('FichajeController - Error obteniendo estado: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error al obtener el estado del fichaje',
                'fichaje_activo' => false
            ], 500);
        }
    }
}
