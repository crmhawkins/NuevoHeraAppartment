<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApartamentoLimpieza;
use App\Models\Fichaje;
use App\Models\User;
use App\Models\Incidencia;
use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use App\Models\Apartamento;
use App\Models\ZonaComun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LimpiadoraDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:LIMPIEZA');
    }

    public function index()
    {
        $user = Auth::user();
        $hoy = Carbon::today();
        
        if (config('app.debug')) {
            Log::info('LimpiadoraDashboardController - Usuario autenticado: ' . $user->id . ' - Email: ' . $user->email . ' - Rol: ' . $user->role);
        }
        
        try {
            // Obtener turno de trabajo de hoy para esta empleada
            $turnoHoy = TurnoTrabajo::where('user_id', $user->id)
                ->whereDate('fecha', $hoy)
                ->with(['tareasAsignadas.tipoTarea', 'tareasAsignadas.apartamento', 'tareasAsignadas.zonaComun'])
                ->first();
            
            // Si no hay turno generado, mostrar mensaje
            if (!$turnoHoy) {
                return view('limpiadora.dashboard', [
                    'datos' => [
                        'diaSemana' => Carbon::now()->locale('es')->isoFormat('dddd'), 'hoy' => Carbon::now()->format('d/m/Y'), 'turnoHoy' => null,
                        'tareasAsignadas' => collect(),
                        'proximasLimpiezas' => collect(),
                        'limpiezasHoy' => 0,
                        'limpiezasAsignadas' => 0,
                        'limpiezasCompletadasHoy' => 0,
                        'limpiezasPendientesHoy' => 0,
                        'apartamentosPendientes' => 0,
                        'incidenciasPendientes' => collect(),
                        'limpiezasSemana' => 0,
                        'limpiezasCompletadasSemana' => 0,
                        'porcentajeSemana' => 0,
                        'fichajeActual' => null,
                        'estadisticasCalidad' => [],
                        'analisisRecientes' => []
                    ]
                ]);
            }
            
            // Obtener tareas asignadas ordenadas por prioridad y orden de ejecución
            $tareasAsignadas = $turnoHoy->tareasAsignadas()
                ->with(['tipoTarea', 'apartamento', 'zonaComun'])
                ->orderBy('prioridad_calculada', 'desc')
                ->orderBy('orden_ejecucion', 'asc')
                ->get();
            
            // Estadísticas del día
            $limpiezasHoy = $tareasAsignadas->count(); // Total de tareas asignadas
            $limpiezasAsignadas = $tareasAsignadas->count(); // Total de tareas asignadas
            
            // Obtener tareas completadas hoy
            $limpiezasCompletadasHoy = $tareasAsignadas->where('estado', 'completada')->count();
                
            // Obtener tareas en progreso hoy
            $limpiezasPendientesHoy = $tareasAsignadas->where('estado', 'en_progreso')->count();
                
            // Preparar tareas para mostrar en el dashboard
            $proximasLimpiezas = $tareasAsignadas->map(function($tarea) use ($hoy) {
                $elemento = null;
                $tipoElemento = 'general';
                $nombreElemento = 'Tarea General';
                
                if ($tarea->apartamento_id) {
                    $elemento = $tarea->apartamento;
                    $tipoElemento = 'apartamento';
                    $nombreElemento = $elemento ? $elemento->titulo : 'Apartamento #' . $tarea->apartamento_id;
                } elseif ($tarea->zona_comun_id) {
                    $elemento = $tarea->zonaComun;
                    $tipoElemento = 'zona_comun';
                    $nombreElemento = $elemento ? $elemento->nombre : 'Zona Común #' . $tarea->zona_comun_id;
                }
                
                return [
                    'id' => $tarea->id,
                    'tipo_tarea' => $tarea->tipoTarea->nombre,
                    'elemento' => $elemento,
                    'tipo_elemento' => $tipoElemento,
                    'nombre_elemento' => $nombreElemento,
                    'prioridad' => $tarea->prioridad_calculada,
                    'orden_ejecucion' => $tarea->orden_ejecucion,
                    'estado' => $tarea->estado,
                    'tiempo_estimado' => $tarea->tipoTarea->tiempo_estimado_minutos,
                    'observaciones' => $tarea->observaciones,
                    'fecha_salida' => $hoy->toDateString(), // Para compatibilidad con la vista
                    'hora_salida' => '00:00', // Para compatibilidad con la vista
                    'status_id' => $tarea->estado === 'completada' ? 2 : ($tarea->estado === 'en_progreso' ? 1 : 0)
                ];
            });
            
            // Obtener incidencias pendientes del usuario
            $incidenciasPendientes = DB::table('incidencias')
                ->where('empleada_id', $user->id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
                
            // Obtener estadísticas de la semana
            $inicioSemana = $hoy->copy()->startOfWeek();
            $finSemana = $hoy->copy()->endOfWeek();
            
            // Tareas asignadas a esta empleada en la semana
            $limpiezasSemana = TurnoTrabajo::where('user_id', $user->id)
                ->whereBetween('fecha', [$inicioSemana, $finSemana])
                ->withCount('tareasAsignadas')
                ->get()
                ->sum('tareas_asignadas_count');
                
            $limpiezasCompletadasSemana = TareaAsignada::whereHas('turno', function($query) use ($user, $inicioSemana, $finSemana) {
                    $query->where('user_id', $user->id)
                          ->whereBetween('fecha', [$inicioSemana, $finSemana]);
                })
                ->where('estado', 'completada')
                ->count();
                
            // Calcular porcentaje de completado de la semana
            $porcentajeSemana = $limpiezasSemana > 0 ? round(($limpiezasCompletadasSemana / $limpiezasSemana) * 100) : 0;
                
            // Obtener estado del fichaje actual (usando la estructura correcta)
            $fichajeActual = DB::table('fichajes')
                ->where('user_id', $user->id)
                ->whereDate('hora_entrada', $hoy)
                ->whereNull('hora_salida')
                ->first();
                
            if (config('app.debug')) {
                Log::info('Dashboard Limpiadora - Fichaje actual encontrado: ' . ($fichajeActual ? 'SÍ - ID: ' . $fichajeActual->id : 'NO'));
                if ($fichajeActual) {
                    Log::info('Dashboard Limpiadora - Detalles fichaje: ID: ' . $fichajeActual->id . ', hora_entrada: ' . $fichajeActual->hora_entrada . ', hora_salida: ' . ($fichajeActual->hora_salida ?? 'NULL'));
                }
            }
                
            // Obtener estadísticas de calidad de limpieza (si existen análisis)
            $analisisRecientes = [];
            try {
                $analisisRecientes = DB::table('photo_analyses')
                    ->where('empleada_id', $user->id)
                    ->whereDate('fecha_analisis', '>=', $hoy->copy()->subDays(7))
                    ->select('calidad_general', DB::raw('count(*) as total'))
                    ->groupBy('calidad_general')
                    ->get()
                    ->pluck('total', 'calidad_general')
                    ->toArray();
            } catch (\Exception $e) {
                // Si hay error, usar array vacío
                $analisisRecientes = [];
            }
            
            // Preparar datos para la vista
            $datos = [
                'turnoHoy' => $turnoHoy,
                'tareasAsignadas' => $tareasAsignadas,
                'limpiezasHoy' => $limpiezasHoy,
                'limpiezasAsignadas' => $limpiezasAsignadas,
                'limpiezasCompletadasHoy' => $limpiezasCompletadasHoy,
                'limpiezasPendientesHoy' => $limpiezasPendientesHoy,
                'apartamentosPendientes' => $tareasAsignadas->where('apartamento_id', '!=', null)->count(),
                'proximasLimpiezas' => $proximasLimpiezas,
                'incidenciasPendientes' => $incidenciasPendientes,
                'limpiezasSemana' => $limpiezasSemana,
                'limpiezasCompletadasSemana' => $limpiezasCompletadasSemana,
                'porcentajeSemana' => $porcentajeSemana,
                'fichajeActual' => $fichajeActual,
                'estadisticasCalidad' => $analisisRecientes,
                'hoy' => $hoy->format('d/m/Y'),
                'diaSemana' => $hoy->locale('es')->dayName
            ];
            
            if (config('app.debug')) {
                Log::info('Dashboard Limpiadora - Datos preparados correctamente, enviando vista');
            }
            return view('limpiadora.dashboard', compact('datos'));
            
        } catch (\Exception $e) {
            // Si hay algún error, devolver vista con datos mínimos
            Log::error('Dashboard Limpiadora - Error general: ' . $e->getMessage());
            Log::error('Dashboard Limpiadora - Stack trace: ' . $e->getTraceAsString());
            
            $datos = [
                'limpiezasHoy' => 0,
                'limpiezasAsignadas' => 0,
                'limpiezasCompletadasHoy' => 0,
                'limpiezasPendientesHoy' => 0,
                'apartamentosPendientes' => 0,
                'proximasLimpiezas' => collect(),
                'incidenciasPendientes' => collect(),
                'limpiezasSemana' => 0,
                'limpiezasCompletadasSemana' => 0,
                'porcentajeSemana' => 0,
                'fichajeActual' => null,
                'analisisRecientes' => [],
                'hoy' => $hoy->format('d/m/Y'),
                'diaSemana' => $hoy->locale('es')->dayName,
                'error' => 'Error al cargar datos: ' . $e->getMessage()
            ];
            
            return view('limpiadora.dashboard', compact('datos'));
        }
    }
    
    public function planificacion(Request $request)
    {
        $user = Auth::user();
        $mes = $request->get('mes') ? Carbon::parse($request->get('mes') . '-01') : Carbon::now()->startOfMonth();

        // Get the empleada_horario for this user
        $horario = \App\Models\EmpleadaHorario::where('user_id', $user->id)->where('activo', 1)->first();

        if (!$horario) {
            return view('limpiadora.planificacion', [
                'diasTrabajo' => [],
                'mes' => $mes,
                'nombreEmpleada' => $user->name,
            ]);
        }

        // Get dias libres for this month's weeks
        $diasLibres = \App\Models\EmpleadaDiasLibres::where('empleada_horario_id', $horario->id)
            ->where('semana_inicio', '>=', $mes->copy()->subWeek()->format('Y-m-d'))
            ->where('semana_inicio', '<=', $mes->copy()->endOfMonth()->format('Y-m-d'))
            ->get();

        // Build day-by-day calendar
        $diasTrabajo = [];
        $inicio = $mes->copy()->startOfMonth();
        $fin = $mes->copy()->endOfMonth();

        for ($dia = $inicio->copy(); $dia->lte($fin); $dia->addDay()) {
            $diaSemana = $dia->dayOfWeek; // 0=Sunday, 1=Monday, ... 6=Saturday

            // Check base schedule (does this user work this day of week?)
            $trabajaBase = match($diaSemana) {
                1 => $horario->lunes,
                2 => $horario->martes,
                3 => $horario->miercoles,
                4 => $horario->jueves,
                5 => $horario->viernes,
                6 => $horario->sabado,
                0 => $horario->domingo,
            };

            // Check if there's a dias_libres entry for the week containing this day
            $libreHoy = false;
            foreach ($diasLibres as $dl) {
                $semanaInicio = Carbon::parse($dl->semana_inicio);
                $semanaFin = $semanaInicio->copy()->addDays(6);

                if ($dia->between($semanaInicio, $semanaFin)) {
                    $diasLibresArray = is_array($dl->dias_libres) ? $dl->dias_libres : (json_decode($dl->dias_libres, true) ?? []);
                    if (in_array((string)$diaSemana, $diasLibresArray)) {
                        $libreHoy = true;
                    }
                    break;
                }
            }

            $diasTrabajo[$dia->format('Y-m-d')] = $trabajaBase && !$libreHoy;
        }

        return view('limpiadora.planificacion', [
            'diasTrabajo' => $diasTrabajo,
            'mes' => $mes,
            'nombreEmpleada' => $user->name,
        ]);
    }

    public function cambiarIdioma(Request $request, $idioma)
    {
        $user = Auth::user();
        if (in_array($idioma, ['es', 'ar'])) {
            $user->idioma_preferido = $idioma;
            $user->save();
        }
        return redirect()->back();
    }

    public function estadisticas()
    {
        $user = Auth::user();
        $hoy = Carbon::today();
        $inicioMes = $hoy->copy()->startOfMonth();
        
        // Estadísticas del mes
        $limpiezasMes = ApartamentoLimpieza::where('empleada_id', $user->id)
            ->whereBetween('fecha_comienzo', [$inicioMes, $hoy])
            ->count();
            
        $limpiezasCompletadasMes = ApartamentoLimpieza::where('empleada_id', $user->id)
            ->whereBetween('fecha_comienzo', [$inicioMes, $hoy])
            ->where('status_id', 2)
            ->count();
            
        // Calcular horas trabajadas del mes
        $horasTrabajadasMes = Fichaje::where('user_id', $user->id)
            ->whereBetween('fecha', [$inicioMes, $hoy])
            ->whereNotNull('hora_fin')
            ->get()
            ->sum(function($fichaje) {
                if ($fichaje->hora_inicio && $fichaje->hora_fin) {
                    $inicio = Carbon::parse($fichaje->hora_inicio);
                    $fin = Carbon::parse($fichaje->hora_fin);
                    return $inicio->diffInHours($fin, false);
                }
                return 0;
            });
            
        return response()->json([
            'limpiezas_mes' => $limpiezasMes,
            'limpiezas_completadas_mes' => $limpiezasCompletadasMes,
            'porcentaje_mes' => $limpiezasMes > 0 ? round(($limpiezasCompletadasMes / $limpiezasMes) * 100) : 0,
            'horas_trabajadas_mes' => round($horasTrabajadasMes, 1)
        ]);
    }
}
