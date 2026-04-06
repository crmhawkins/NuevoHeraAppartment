<?php

namespace App\Http\Controllers;

use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use App\Models\TipoTarea;
use App\Models\HorasExtras;
use App\Models\EmpleadaHorario;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LimpiadoraTurnosController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (Auth::user()->role !== 'LIMPIEZA') {
                abort(403, 'No tienes permisos para acceder a esta sección');
            }
            return $next($request);
        });
    }

    /**
     * Mostrar listado de turnos de la limpiadora logueada
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Obtener la semana seleccionada (por defecto la actual)
        $semanaOffset = $request->get('semana', 0); // 0 = semana actual, -1 = anterior, 1 = siguiente
        $hoy = Carbon::now();
        $inicioSemana = $hoy->copy()->startOfWeek()->addWeeks($semanaOffset);
        $finSemana = $hoy->copy()->endOfWeek()->addWeeks($semanaOffset);
        
        // Obtener turnos de la semana seleccionada
        $turnos = TurnoTrabajo::where('user_id', $user->id)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->with(['tareasAsignadas.tipoTarea', 'tareasAsignadas.apartamento', 'tareasAsignadas.zonaComun'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->get();
        
        // Obtener horario de trabajo de la empleada
        $horarioEmpleada = \DB::table('empleada_horarios')
            ->where('user_id', $user->id)
            ->first();
        
        $horarioPorDefecto = [
            'hora_inicio' => $horarioEmpleada && isset($horarioEmpleada->hora_inicio) ? $horarioEmpleada->hora_inicio : '09:00:00',
            'hora_fin' => $horarioEmpleada && isset($horarioEmpleada->hora_fin) ? $horarioEmpleada->hora_fin : '17:00:00'
        ];
        
        // Obtener días libres de la semana desde empleada_dias_libres
        $diasLibres = [];
        $empleadaDiasLibres = \DB::table('empleada_dias_libres')
            ->join('empleada_horarios', 'empleada_dias_libres.empleada_horario_id', '=', 'empleada_horarios.id')
            ->where('empleada_horarios.user_id', $user->id)
            ->where('empleada_dias_libres.semana_inicio', '<=', $finSemana)
            ->where('empleada_dias_libres.semana_inicio', '>=', $inicioSemana->copy()->subDays(7))
            ->select('empleada_dias_libres.*')
            ->get();
        
        // Si no hay días libres específicos para esta semana, buscar en semanas anteriores
        if($empleadaDiasLibres->isEmpty()) {
            $empleadaDiasLibres = \DB::table('empleada_dias_libres')
                ->join('empleada_horarios', 'empleada_dias_libres.empleada_horario_id', '=', 'empleada_horarios.id')
                ->where('empleada_horarios.user_id', $user->id)
                ->where('empleada_dias_libres.semana_inicio', '<=', $finSemana)
                ->orderBy('empleada_dias_libres.semana_inicio', 'desc')
                ->select('empleada_dias_libres.*')
                ->get();
        }
        
        foreach($empleadaDiasLibres as $diaLibre) {
            $semanaInicio = Carbon::parse($diaLibre->semana_inicio);
            $diasLibresArray = json_decode($diaLibre->dias_libres, true);
            
            if(is_array($diasLibresArray)) {
                foreach($diasLibresArray as $diaNumero) {
                    // Los números de días libres están basados en el día de la semana (0=domingo, 6=sábado)
                    // semana_inicio es un lunes, así que calculamos desde ahí
                    $fecha = $semanaInicio->copy(); // Empezar desde el lunes
                    
                    // Calcular el desplazamiento correcto desde el lunes
                    // 0=domingo (6 días después del lunes), 1=lunes (0 días), ..., 6=sábado (5 días)
                    $desplazamiento = ($diaNumero == 0) ? 6 : $diaNumero - 1;
                    $fecha = $fecha->addDays($desplazamiento);
                    
                    if($fecha >= $inicioSemana && $fecha <= $finSemana) {
                        $diasLibres[] = $fecha->format('Y-m-d');
                    }
                }
            }
        }
        
        // Si no hay días libres específicos configurados, usar la configuración general del horario
        if(empty($diasLibres) && $horarioEmpleada) {
            for ($i = 0; $i < 7; $i++) {
                $fecha = $inicioSemana->copy()->addDays($i);
                $diaSemana = $fecha->dayOfWeek; // 0=domingo, 1=lunes, ..., 6=sábado
                
                $diasColumnas = [
                    1 => 'lunes',
                    2 => 'martes', 
                    3 => 'miercoles',
                    4 => 'jueves',
                    5 => 'viernes',
                    6 => 'sabado',
                    0 => 'domingo'
                ];
                
                $columnaDia = $diasColumnas[$diaSemana] ?? 'lunes';
                $trabajaEseDia = $horarioEmpleada->$columnaDia ?? false;
                
                if(!$trabajaEseDia) {
                    $diasLibres[] = $fecha->format('Y-m-d');
                }
            }
        }
        
        // Agrupar turnos por día
        $turnosPorDia = $turnos->groupBy(function($turno) {
            return Carbon::parse($turno->fecha)->format('Y-m-d');
        });
        
        // Crear estructura de días de la semana
        $diasSemana = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicioSemana->copy()->addDays($i);
            $fechaStr = $fecha->format('Y-m-d');
            
            $diasSemana[] = [
                'fecha' => $fecha,
                'fecha_str' => $fechaStr,
                'nombre_dia' => $fecha->locale('es')->dayName,
                'numero_dia' => $fecha->day,
                'es_hoy' => $fecha->isToday(),
                'es_libre' => in_array($fechaStr, $diasLibres),
                'turnos' => $turnosPorDia->get($fechaStr, collect())
            ];
        }

        // Estadísticas de la semana
        $estadisticas = [
            'total_turnos' => $turnos->count(),
            'turnos_completados' => $turnos->where('estado', 'completado')->count(),
            'turnos_pendientes' => $turnos->where('estado', 'pendiente')->count(),
            'turnos_en_progreso' => $turnos->where('estado', 'en_progreso')->count(),
            'total_tareas' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->count();
            }),
            'tareas_completadas' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->where('estado', 'completada')->count();
            }),
            'tareas_pendientes' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->whereIn('estado', ['pendiente', 'en_progreso'])->count();
            }),
            'tiempo_estimado_total' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->sum(function($tarea) {
                    return $tarea->tipoTarea->tiempo_estimado_minutos;
                });
            }),
            'dias_libres' => count($diasLibres)
        ];

        return view('limpiadora.turnos.index', compact('diasSemana', 'estadisticas', 'user', 'inicioSemana', 'finSemana', 'semanaOffset', 'horarioPorDefecto'));
    }

    /**
     * Mostrar detalles de un turno específico
     */
    public function show(TurnoTrabajo $turno)
    {
        // Verificar que el turno pertenece a la limpiadora logueada
        if ($turno->user_id !== Auth::id()) {
            abort(403, 'No tienes permisos para ver este turno');
        }

        $turno->load([
            'tareasAsignadas.tipoTarea', 
            'tareasAsignadas.apartamento', 
            'tareasAsignadas.zonaComun'
        ]);

        return view('limpiadora.turnos.show', compact('turno'));
    }

    /**
     * Iniciar un turno
     */
    public function iniciarTurno(TurnoTrabajo $turno)
    {
        // Verificar que el turno pertenece a la limpiadora logueada
        if ($turno->user_id !== Auth::id()) {
            abort(403, 'No tienes permisos para iniciar este turno');
        }

        try {
            $turno->iniciarTurno();
            
            return response()->json([
                'success' => true,
                'message' => 'Turno iniciado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error iniciando turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el turno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalizar un turno
     */
    public function finalizarTurno(Request $request, TurnoTrabajo $turno)
    {
        // Verificar que el turno pertenece a la limpiadora logueada
        if ($turno->user_id !== Auth::id()) {
            abort(403, 'No tienes permisos para finalizar este turno');
        }

        $request->validate([
            'horas_trabajadas' => 'nullable|numeric|min:0|max:24',
            'observaciones' => 'nullable|string|max:1000',
            'motivo_horas_extras' => 'nullable|string|max:500'
        ]);

        try {
            $turno->finalizarTurno($request->horas_trabajadas);
            
            if ($request->observaciones) {
                $turno->update(['observaciones' => $request->observaciones]);
            }

            // Verificar si hay horas extras y crearlas automáticamente
            $this->procesarHorasExtras($turno, $request->motivo_horas_extras);
            
            return response()->json([
                'success' => true,
                'message' => 'Turno finalizado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error finalizando turno: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al finalizar el turno: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar horas extras automáticamente
     */
    private function procesarHorasExtras(TurnoTrabajo $turno, $motivo = null)
    {
        try {
            // Obtener horas contratadas de la empleada
            $empleadaHorario = EmpleadaHorario::where('user_id', $turno->user_id)->first();
            $horasContratadas = $empleadaHorario ? $empleadaHorario->horas_contratadas_dia : 8.0;
            
            $horasTrabajadas = $turno->horas_trabajadas ?? 0;
            
            // Solo crear horas extras si hay exceso
            if ($horasTrabajadas > $horasContratadas) {
                $horasExtras = $horasTrabajadas - $horasContratadas;
                
                // Verificar si ya existe un registro de horas extras para este turno
                $existeHorasExtras = HorasExtras::where('turno_id', $turno->id)->exists();
                
                if (!$existeHorasExtras) {
                    HorasExtras::create([
                        'user_id' => $turno->user_id,
                        'turno_id' => $turno->id,
                        'fecha' => $turno->fecha,
                        'horas_contratadas' => $horasContratadas,
                        'horas_trabajadas' => $horasTrabajadas,
                        'horas_extras' => $horasExtras,
                        'motivo' => $motivo ?? 'Trabajo adicional requerido',
                        'estado' => HorasExtras::ESTADO_PENDIENTE
                    ]);
                    
                    Log::info("Horas extras creadas para turno {$turno->id}: {$horasExtras}h extras");
                }
            }
        } catch (\Exception $e) {
            Log::error('Error procesando horas extras: ' . $e->getMessage());
        }
    }

    /**
     * Iniciar una tarea específica
     */
    public function iniciarTarea(TareaAsignada $tarea)
    {
        // Verificar que la tarea pertenece a un turno de la limpiadora logueada
        if ($tarea->turno->user_id !== Auth::id()) {
            abort(403, 'No tienes permisos para iniciar esta tarea');
        }

        try {
            $tarea->iniciarTarea();
            
            return response()->json([
                'success' => true,
                'message' => 'Tarea iniciada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error iniciando tarea: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Completar una tarea específica
     */
    public function completarTarea(Request $request, TareaAsignada $tarea)
    {
        // Verificar que la tarea pertenece a un turno de la limpiadora logueada
        if ($tarea->turno->user_id !== Auth::id()) {
            abort(403, 'No tienes permisos para completar esta tarea');
        }

        $request->validate([
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            $tarea->completarTarea($request->observaciones);
            
            // Buscar y actualizar el ApartamentoLimpieza asociado
            $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
            if ($apartamentoLimpieza) {
                $apartamentoLimpieza->update([
                    'status_id' => 3, // Limpio
                    'fecha_fin' => now()
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Tarea completada exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error completando tarea: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de la limpiadora
     */
    public function estadisticas(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfWeek());
        $fechaFin = $request->get('fecha_fin', now()->endOfWeek());
        
        $turnos = TurnoTrabajo::where('user_id', Auth::id())
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->with(['tareasAsignadas'])
            ->get();
            
        $estadisticas = [
            'total_turnos' => $turnos->count(),
            'turnos_completados' => $turnos->where('estado', 'completado')->count(),
            'turnos_en_progreso' => $turnos->where('estado', 'en_progreso')->count(),
            'total_tareas' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->count();
            }),
            'tareas_completadas' => $turnos->sum(function($turno) {
                return $turno->tareasAsignadas->where('estado', 'completada')->count();
            }),
            'horas_trabajadas' => $turnos->sum('horas_trabajadas'),
            'por_dia' => $turnos->groupBy(function($turno) {
                return $turno->fecha->format('Y-m-d');
            })->map(function($turnosDia) {
                return [
                    'fecha' => $turnosDia->first()->fecha->format('d/m/Y'),
                    'turnos' => $turnosDia->count(),
                    'tareas_completadas' => $turnosDia->sum(function($turno) {
                        return $turno->tareasAsignadas->where('estado', 'completada')->count();
                    }),
                    'horas_trabajadas' => $turnosDia->sum('horas_trabajadas')
                ];
            })
        ];
        
        return response()->json($estadisticas);
    }
}
