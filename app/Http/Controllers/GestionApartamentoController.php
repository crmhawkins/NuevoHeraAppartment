<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\ApartamentoLimpieza;
use App\Models\Fichaje;
use App\Models\Pausa;
use App\Models\GestionApartamento;
use App\Models\LimpiezaFondo;
use App\Models\Reserva;
use App\Models\TurnoTrabajo;
use App\Models\TareaAsignada;
use Carbon\Carbon;
use App\Services\AmenityConsumptionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\Auth; // Añade esta línea
use App\Models\Checklist;
use App\Models\ApartamentoLimpiezaItem;
use App\Services\AlertService;
use App\Services\ArticuloStockService;

class GestionApartamentoController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */


    public function index()
    {
        $user = Auth::user();
        $hoy = Carbon::today();

        // Verificar si hay turnos generados para hoy
        $turnoHoy = TurnoTrabajo::where('user_id', $user->id)
            ->whereDate('fecha', $hoy)
            ->with(['tareasAsignadas.tipoTarea', 'tareasAsignadas.apartamento', 'tareasAsignadas.zonaComun'])
            ->first();

        // Si hay turno generado, usar el nuevo sistema
        if ($turnoHoy) {
            return $this->indexConTurnos($turnoHoy, $hoy);
        }

        // Si no hay turno para hoy: si la empleada usa turnos, mostrar jornada vacía
        $usaTurnos = TurnoTrabajo::where('user_id', $user->id)->exists();
        if ($usaTurnos) {
            return $this->indexSinTurnoHoy($hoy);
        }

        // Si no hay turno y no usa turnos, usar el sistema antiguo (listado global)
        $reservasPendientes = Reserva::apartamentosPendiente();

        $reservasOcupados = Reserva::apartamentosOcupados();
        $reservasSalida = Reserva::apartamentosSalida();
        // $reservasLimpieza = Reserva::apartamentosLimpiados();
        $reservasLimpieza = ApartamentoLimpieza::apartamentosLimpiados()->with(['apartamento', 'zonaComun', 'estado'])->get();
        $reservasEnLimpieza = ApartamentoLimpieza::apartamentosEnLimpiados()->with(['apartamento', 'zonaComun'])->get();

        // Obtener apartamentos previstos para mañana (los que SALEN mañana para limpiar)
        $manana = now()->addDay()->toDateString();
        $reservasManana = Reserva::where('fecha_salida', $manana)
            ->where(function($query) {
                $query->where('estado_id', '!=', 4)
                      ->orWhereNull('estado_id');
            })
            ->with(['apartamento'])
            ->orderBy('apartamento_id')
            ->get();

        // --- Pre-load siguiente reserva and entrada data to avoid N+1 queries ---
        $hoyStr = now()->toDateString();
        $selectFields = ['id', 'apartamento_id', 'fecha_entrada', 'fecha_salida', 'numero_personas', 'numero_ninos', 'edades_ninos', 'notas_ninos', 'codigo_reserva'];
        $estadoFilter = function($query) {
            $query->where('estado_id', '!=', 4)->orWhereNull('estado_id');
        };

        // Collect all apartment IDs across all collections
        $allApartamentoIds = collect()
            ->merge($reservasPendientes->where('limpieza_fondo', false)->pluck('apartamento_id'))
            ->merge($reservasEnLimpieza->pluck('apartamento_id'))
            ->merge($reservasLimpieza->pluck('apartamento_id'))
            ->merge($reservasManana->pluck('apartamento_id'))
            ->unique()
            ->filter()
            ->values();

        // Batch query: all future reservations for these apartments (for siguiente_reserva lookups)
        // Grouped by apartamento_id, ordered by fecha_entrada so ->first() gives the nearest
        $futureReservasByApto = collect();
        $entradasHoyByApto = collect();
        $entradasMananaByApto = collect();

        if ($allApartamentoIds->isNotEmpty()) {
            $futureReservasByApto = Reserva::whereIn('apartamento_id', $allApartamentoIds)
                ->where('fecha_entrada', '>', $hoyStr)
                ->where($estadoFilter)
                ->orderBy('fecha_entrada', 'asc')
                ->select($selectFields)
                ->get()
                ->groupBy('apartamento_id');

            $entradasHoyByApto = Reserva::whereIn('apartamento_id', $allApartamentoIds)
                ->where('fecha_entrada', $hoyStr)
                ->where($estadoFilter)
                ->select($selectFields)
                ->get()
                ->groupBy('apartamento_id');

            $entradasMananaByApto = Reserva::whereIn('apartamento_id', $allApartamentoIds)
                ->where('fecha_entrada', $manana)
                ->where($estadoFilter)
                ->select($selectFields)
                ->get()
                ->groupBy('apartamento_id');
        }

        // Loop 1: reservasPendientes - assign siguienteReserva and reserva_entra_hoy from pre-loaded data
        foreach ($reservasPendientes as $reserva) {
            if (!$reserva->limpieza_fondo) {
                try {
                    // siguienteReserva: first reservation entering AFTER this reservation's checkout date
                    $reserva->siguienteReserva = $futureReservasByApto
                        ->get($reserva->apartamento_id, collect())
                        ->where('fecha_entrada', '>', $reserva->fecha_salida)
                        ->first();
                } catch (Exception $e) {
                    $reserva->siguienteReserva = null;
                }

                try {
                    // reserva_entra_hoy: reservation entering on the same day this one checks out
                    $candidates = $entradasHoyByApto->get($reserva->apartamento_id, collect());
                    // If fecha_salida is today, also check entradas for today; otherwise check future
                    if ($reserva->fecha_salida === $hoyStr) {
                        $reserva->reserva_entra_hoy = $candidates
                            ->where('id', '!=', $reserva->id)
                            ->first();
                    } else {
                        // Original query matched fecha_entrada == fecha_salida, which may not be today
                        // Fall back to future reservations entering on fecha_salida
                        $reserva->reserva_entra_hoy = $futureReservasByApto
                            ->get($reserva->apartamento_id, collect())
                            ->where('fecha_entrada', $reserva->fecha_salida)
                            ->where('id', '!=', $reserva->id)
                            ->first();
                    }
                } catch (Exception $e) {
                    $reserva->reserva_entra_hoy = null;
                }
            }
        }

        // Loop 2: reservasEnLimpieza - assign siguiente_reserva and reserva_entra_hoy
        foreach ($reservasEnLimpieza as $limpieza) {
            $limpieza->siguiente_reserva = $futureReservasByApto
                ->get($limpieza->apartamento_id, collect())
                ->first();
            $limpieza->reserva_entra_hoy = $entradasHoyByApto
                ->get($limpieza->apartamento_id, collect())
                ->first();
        }

        // Loop 3: reservasLimpieza (completed) - assign siguiente_reserva and reserva_entra_hoy
        foreach ($reservasLimpieza as $limpieza) {
            $limpieza->siguiente_reserva = $futureReservasByApto
                ->get($limpieza->apartamento_id, collect())
                ->first();
            $limpieza->reserva_entra_hoy = $entradasHoyByApto
                ->get($limpieza->apartamento_id, collect())
                ->first();
        }

        // Loop 4: reservasManana - assign siguiente_reserva and reserva_entra_manana
        foreach ($reservasManana as $reserva) {
            $reserva->siguiente_reserva = $futureReservasByApto
                ->get($reserva->apartamento_id, collect())
                ->where('fecha_entrada', '>', $reserva->fecha_salida)
                ->first();
            $reserva->reserva_entra_manana = $entradasMananaByApto
                ->get($reserva->apartamento_id, collect())
                ->first();
        }


        $hoy = now()->toDateString();
        $limpiezaFondo = LimpiezaFondo::whereDate('fecha', $hoy)->get();

        // Obtener amenities de consumo para todas las secciones
        $amenities = \App\Models\Amenity::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Obtener consumos existentes para todas las limpiezas de hoy
        $limpiezaIds = collect([$reservasPendientes, $reservasEnLimpieza, $reservasLimpieza])
            ->flatten()
            ->pluck('id')
            ->filter()
            ->toArray();

        $consumosExistentes = \App\Models\AmenityConsumo::whereIn('limpieza_id', $limpiezaIds)
            ->with('amenity')
            ->get()
            ->groupBy('limpieza_id');

        // Obtener estadísticas del dashboard de limpieza
        $dashboardStats = $this->getDashboardStats();

        $tareasParaConsola = [
            'fecha' => $hoy,
            'tieneTurnoHoy' => false,
            'tareas' => [],
            'mensaje' => 'Sistema antiguo: se muestran apartamentos pendientes del día (no filtrado por turno).',
        ];

        return view('gestion.index', compact(
            'reservasPendientes',
            'reservasOcupados',
            'reservasSalida',
            'reservasLimpieza',
            'reservasEnLimpieza',
            'limpiezaFondo',
            'reservasManana',
            'amenities',
            'consumosExistentes',
            'dashboardStats',
            'tareasParaConsola'
        ));
    }

    /**
     * Vista de gestión cuando la empleada usa turnos pero no tiene turno para hoy (jornada vacía)
     */
    private function indexSinTurnoHoy($hoy)
    {
        $user = Auth::user();
        $reservasPendientes = collect();
        $reservasEnLimpieza = collect();
        $reservasLimpieza = collect();
        $reservasOcupados = collect();
        $reservasSalida = collect();
        $reservasManana = collect();
        $limpiezaFondo = collect();
        $amenities = \App\Models\Amenity::activos()->where('categoria', 'Otros')->orderBy('categoria')->orderBy('nombre')->get()->groupBy('categoria');
        $consumosExistentes = collect();
        $dashboardStats = $this->getDashboardStats();

        $tareasParaConsola = [
            'fecha' => $hoy->toDateString(),
            'tieneTurnoHoy' => false,
            'tareas' => [],
            'mensaje' => 'No tienes turno asignado para hoy. Las tareas que tienes pueden estar en otra fecha. Revisa "Mis Turnos" para ver tus tareas por día.',
        ];
        $sinTurnoHoy = true;

        return view('gestion.index', compact(
            'reservasPendientes',
            'reservasOcupados',
            'reservasSalida',
            'reservasLimpieza',
            'reservasEnLimpieza',
            'limpiezaFondo',
            'reservasManana',
            'amenities',
            'consumosExistentes',
            'dashboardStats',
            'tareasParaConsola',
            'sinTurnoHoy'
        ));
    }

    /**
     * Mostrar gestión con el nuevo sistema de turnos
     */
    private function indexConTurnos($turnoHoy, $hoy)
    {
        $user = Auth::user();

        // Obtener tareas asignadas ordenadas por prioridad y orden de ejecución
        $tareasAsignadas = $turnoHoy->tareasAsignadas()
            ->with(['tipoTarea', 'apartamento', 'zonaComun'])
            ->orderBy('prioridad_calculada', 'desc')
            ->orderBy('orden_ejecucion', 'asc')
            ->get();

        // Preparar datos para la vista usando el formato del sistema antiguo
        $reservasPendientes = collect();
        $reservasEnLimpieza = collect();
        $reservasLimpieza = collect();

        // Convertir tareas asignadas al formato esperado por la vista
        foreach ($tareasAsignadas as $tarea) {
            if ($tarea->apartamento_id) {
                // Es una tarea de apartamento
                $apartamento = $tarea->apartamento;
                if ($apartamento) {
                    // Buscar la próxima reserva del apartamento después de hoy
                    $proximaReserva = \App\Models\Reserva::where('apartamento_id', $apartamento->id)
                        ->where('fecha_entrada', '>', $hoy->toDateString())
                        ->where(function($query) {
                            $query->where('estado_id', '!=', 4)
                                  ->orWhereNull('estado_id');
                        })
                        ->orderBy('fecha_entrada', 'asc')
                        ->first();

                    // Crear un objeto similar a Reserva para compatibilidad
                    $reserva = new \stdClass();
                    $reserva->id = $tarea->id;
                    $reserva->apartamento_id = $apartamento->id;
                    $reserva->apartamento = $apartamento;
                    $reserva->codigo_reserva = 'TAREA-' . $tarea->id;
                    $reserva->numero_personas = 0; // No aplicable para tareas
                    $reserva->fecha_salida = $hoy->toDateString();
                    $reserva->fecha_entrada = $hoy->toDateString();
                    $reserva->limpieza_fondo = false;
                    $reserva->tarea_asignada = $tarea;
                    $reserva->tipo_tarea = $tarea->tipoTarea;
                    $reserva->prioridad = $tarea->prioridad_calculada;
                    $reserva->orden_ejecucion = $tarea->orden_ejecucion;
                    $reserva->estado = $tarea->estado;
                    $reserva->tiempo_estimado = $tarea->tipoTarea->tiempo_estimado_minutos;
                    // Información de la próxima reserva
                    $reserva->proximaReserva = $proximaReserva;
                    // Añadir status_id para compatibilidad con la vista
                    $reserva->status_id = $tarea->estado === 'completada' ? 3 : ($tarea->estado === 'en_progreso' ? 2 : 1);

                    // Agregar a la colección apropiada según el estado
                    if ($tarea->estado === 'pendiente') {
                        $reservasPendientes->push($reserva);
                    } elseif ($tarea->estado === 'en_progreso') {
                        $reservasEnLimpieza->push($reserva);
                    } elseif ($tarea->estado === 'completada') {
                        $reservasLimpieza->push($reserva);
                    }
                }
            } elseif ($tarea->zona_comun_id) {
                // Es una tarea de zona común
                $zonaComun = $tarea->zonaComun;
                if ($zonaComun) {
                    $reserva = new \stdClass();
                    $reserva->id = $tarea->id;
                    $reserva->zona_comun_id = $zonaComun->id;
                    $reserva->zonaComun = $zonaComun;
                    $reserva->codigo_reserva = 'ZONA-' . $tarea->id;
                    $reserva->numero_personas = 0;
                    $reserva->fecha_salida = $hoy->toDateString();
                    $reserva->fecha_entrada = $hoy->toDateString();
                    $reserva->limpieza_fondo = false;
                    $reserva->tarea_asignada = $tarea;
                    $reserva->tipo_tarea = $tarea->tipoTarea;
                    $reserva->prioridad = $tarea->prioridad_calculada;
                    $reserva->orden_ejecucion = $tarea->orden_ejecucion;
                    $reserva->estado = $tarea->estado;
                    $reserva->tiempo_estimado = $tarea->tipoTarea->tiempo_estimado_minutos;
                    // Añadir status_id para compatibilidad con la vista
                    $reserva->status_id = $tarea->estado === 'completada' ? 3 : ($tarea->estado === 'en_progreso' ? 2 : 1);

                    if ($tarea->estado === 'pendiente') {
                        $reservasPendientes->push($reserva);
                    } elseif ($tarea->estado === 'en_progreso') {
                        $reservasEnLimpieza->push($reserva);
                    } elseif ($tarea->estado === 'completada') {
                        $reservasLimpieza->push($reserva);
                    }
                }
            } else {
                // Es una tarea general
                $reserva = new \stdClass();
                $reserva->id = $tarea->id;
                $reserva->apartamento_id = null;
                $reserva->apartamento = null;
                $reserva->zona_comun_id = null;
                $reserva->zonaComun = null;
                $reserva->codigo_reserva = 'GENERAL-' . $tarea->id;
                $reserva->numero_personas = 0;
                $reserva->fecha_salida = $hoy->toDateString();
                $reserva->fecha_entrada = $hoy->toDateString();
                $reserva->limpieza_fondo = false;
                $reserva->tarea_asignada = $tarea;
                $reserva->tipo_tarea = $tarea->tipoTarea;
                $reserva->prioridad = $tarea->prioridad_calculada;
                $reserva->orden_ejecucion = $tarea->orden_ejecucion;
                $reserva->estado = $tarea->estado;
                $reserva->tiempo_estimado = $tarea->tipoTarea->tiempo_estimado_minutos;
                // Añadir status_id para compatibilidad con la vista
                $reserva->status_id = $tarea->estado === 'completada' ? 3 : ($tarea->estado === 'en_progreso' ? 2 : 1);

                if ($tarea->estado === 'pendiente') {
                    $reservasPendientes->push($reserva);
                } elseif ($tarea->estado === 'en_progreso') {
                    $reservasEnLimpieza->push($reserva);
                } elseif ($tarea->estado === 'completada') {
                    $reservasLimpieza->push($reserva);
                }
            }
        }

        // Ordenar las colecciones por prioridad y orden de ejecución
        $reservasPendientes = $reservasPendientes->sortBy([
            ['prioridad', 'desc'],
            ['orden_ejecucion', 'asc']
        ])->values();

        $reservasEnLimpieza = $reservasEnLimpieza->sortBy([
            ['prioridad', 'desc'],
            ['orden_ejecucion', 'asc']
        ])->values();

        $reservasLimpieza = $reservasLimpieza->sortBy([
            ['prioridad', 'desc'],
            ['orden_ejecucion', 'asc']
        ])->values();

        // Obtener solo amenities de categoría "Otros" para el modal de limpiadoras
        $amenities = \App\Models\Amenity::activos()
            ->where('categoria', 'Otros')
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Obtener estadísticas del dashboard
        $dashboardStats = $this->getDashboardStatsConTurnos($turnoHoy, $tareasAsignadas);

        // Datos adicionales para compatibilidad
        $reservasOcupados = collect();
        $reservasSalida = collect();
        $limpiezaFondo = collect();
        $reservasManana = collect();
        $consumosExistentes = collect();

        // Lista de tareas para depuración en consola
        $tareasParaConsola = [
            'fecha' => $hoy->toDateString(),
            'tieneTurnoHoy' => true,
            'tareas' => $tareasAsignadas->map(function ($t) {
                $elemento = $t->apartamento_id ? ($t->apartamento->titulo ?? '') : ($t->zona_comun_id ? ($t->zonaComun->nombre ?? '') : $t->tipoTarea->nombre ?? '');
                return ['id' => $t->id, 'tipo' => $t->tipoTarea->nombre ?? '', 'elemento' => $elemento, 'estado' => $t->estado];
            })->toArray(),
            'total' => $tareasAsignadas->count(),
            'mensaje' => null,
        ];

        return view('gestion.index', compact(
            'reservasPendientes',
            'reservasOcupados',
            'reservasSalida',
            'reservasLimpieza',
            'reservasEnLimpieza',
            'limpiezaFondo',
            'reservasManana',
            'amenities',
            'consumosExistentes',
            'dashboardStats',
            'turnoHoy',
            'tareasParaConsola'
        ));
    }

    /**
     * Obtener estadísticas del dashboard con el nuevo sistema de turnos
     */
    private function getDashboardStatsConTurnos($turnoHoy, $tareasAsignadas)
    {
        $user = Auth::user();
        $hoy = Carbon::today();

        // Estadísticas del día
        $limpiezasHoy = $tareasAsignadas->count();
        $limpiezasAsignadas = $tareasAsignadas->count();
        $limpiezasCompletadasHoy = $tareasAsignadas->where('estado', 'completada')->count();
        $limpiezasPendientesHoy = $tareasAsignadas->where('estado', 'pendiente')->count();

        // Apartamentos pendientes (tareas de apartamento pendientes)
        $apartamentosPendientes = $tareasAsignadas->where('apartamento_id', '!=', null)
            ->where('estado', 'pendiente')
            ->count();

        // Obtener incidencias pendientes del usuario
        $incidenciasPendientes = \DB::table('incidencias')
            ->where('empleada_id', $user->id)
            ->where('estado', 'pendiente')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Obtener estadísticas de la semana
        $inicioSemana = $hoy->copy()->startOfWeek();
        $finSemana = $hoy->copy()->endOfWeek();

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

        $porcentajeSemana = $limpiezasSemana > 0 ? round(($limpiezasCompletadasSemana / $limpiezasSemana) * 100, 1) : 0;

        // Obtener fichaje actual (comentado temporalmente por error de columna)
        $fichajeActual = null;
        // $fichajeActual = Fichaje::where('user_id', $user->id)
        //     ->whereNull('fecha_fin')
        //     ->first();

        // Estadísticas de calidad (placeholder)
        $estadisticasCalidad = [];

        return [
            'limpiezasHoy' => $limpiezasHoy,
            'limpiezasAsignadas' => $limpiezasAsignadas,
            'limpiezasCompletadasHoy' => $limpiezasCompletadasHoy,
            'limpiezasPendientesHoy' => $limpiezasPendientesHoy,
            'apartamentosPendientes' => $apartamentosPendientes,
            'incidenciasPendientes' => $incidenciasPendientes,
            'limpiezasSemana' => $limpiezasSemana,
            'limpiezasCompletadasSemana' => $limpiezasCompletadasSemana,
            'porcentajeSemana' => $porcentajeSemana,
            'fichajeActual' => $fichajeActual,
            'estadisticasCalidad' => $estadisticasCalidad
        ];
    }

    /**
     * Obtener información de una tarea asignada
     */
    public function infoTarea(TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Cargar relaciones necesarias
            $tarea->load(['tipoTarea', 'apartamento.edificio', 'zonaComun', 'turno.user']);

            // Obtener checklist si existe
            $checklist = $tarea->checklist();
            $itemsChecklist = $tarea->itemChecklists();

            // Preparar información de la tarea
            $info = [
                'id' => $tarea->id,
                'tipo_tarea' => $tarea->tipoTarea->nombre,
                'categoria' => $tarea->tipoTarea->categoria,
                'prioridad' => $tarea->prioridad_calculada,
                'orden_ejecucion' => $tarea->orden_ejecucion,
                'tiempo_estimado' => $tarea->tipoTarea->tiempo_estimado_minutos,
                'estado' => $tarea->estado,
                'observaciones' => $tarea->observaciones,
                'fecha_asignacion' => $tarea->created_at->format('d/m/Y H:i'),
                'empleada' => $tarea->turno->user->name,
                'elemento' => null,
                'checklist' => null,
                'items_checklist' => []
            ];

            // Información del elemento (apartamento, zona común, etc.)
            if ($tarea->apartamento_id) {
                $info['elemento'] = [
                    'tipo' => 'apartamento',
                    'nombre' => $tarea->apartamento->titulo,
                    'edificio' => $tarea->apartamento->edificio->nombre ?? 'N/A'
                ];
            } elseif ($tarea->zona_comun_id) {
                $info['elemento'] = [
                    'tipo' => 'zona_comun',
                    'nombre' => $tarea->zonaComun->nombre,
                    'descripcion' => $tarea->zonaComun->descripcion ?? 'N/A'
                ];
            } else {
                $info['elemento'] = [
                    'tipo' => 'general',
                    'nombre' => $tarea->tipoTarea->nombre
                ];
            }

            // Información del checklist
            if ($checklist) {
                $info['checklist'] = [
                    'id' => $checklist->id,
                    'nombre' => $checklist->nombre,
                    'descripcion' => $checklist->descripcion ?? 'N/A'
                ];

                $info['items_checklist'] = $itemsChecklist->map(function($item) {
                    return [
                        'id' => $item->id,
                        'nombre' => $item->nombre,
                        'descripcion' => $item->descripcion ?? 'N/A',
                        'categoria' => $item->categoria ?? 'N/A',
                        'tiene_stock' => $item->tiene_stock,
                        'tiene_averias' => $item->tiene_averias
                    ];
                });
            }

            return response()->json([
                'success' => true,
                'data' => $info
            ]);

        } catch (\Exception $e) {
            Log::error('Error obteniendo información de tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Iniciar una tarea asignada
     */
    public function iniciarTarea(TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Verificar que la tarea está en estado pendiente
            if ($tarea->estado !== 'pendiente') {
                return response()->json(['error' => 'La tarea no está en estado pendiente'], 400);
            }

            // Actualizar estado de la tarea usando el método del modelo que guarda fecha_inicio_real
            $tarea->iniciarTarea();

            // Si es una tarea de apartamento o zona común, crear ApartamentoLimpieza real
            if ($tarea->apartamento_id || $tarea->zona_comun_id) {
                $this->crearApartamentoLimpiezaParaTarea($tarea);
            }

            // Log de la acción
            Log::info('Tarea iniciada', [
                'tarea_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'tipo_tarea' => $tarea->tipoTarea->nombre,
                'fecha_inicio_real' => $tarea->fecha_inicio_real
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea iniciada correctamente',
                'data' => [
                    'tarea_id' => $tarea->id,
                    'estado' => $tarea->estado,
                    'fecha_inicio_real' => $tarea->fecha_inicio_real ? $tarea->fecha_inicio_real->format('d/m/Y H:i') : now()->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error iniciando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Finalizar una tarea asignada
     */
    public function finalizarTarea(TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Verificar que la tarea está en estado en_progreso
            if ($tarea->estado !== 'en_progreso') {
                return response()->json(['error' => 'La tarea no está en estado en progreso'], 400);
            }

            // Actualizar estado de la tarea
            $tarea->update([
                'estado' => 'completada',
                'fecha_fin_real' => now()
            ]);

            // Buscar y actualizar el ApartamentoLimpieza asociado
            $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
            if ($apartamentoLimpieza) {
                $hoy = Carbon::now();
                $apartamentoLimpieza->update([
                    'status_id' => 3, // Limpio
                    'fecha_fin' => $hoy
                ]);

                Log::info('ApartamentoLimpieza actualizado desde finalizarTarea', [
                    'limpieza_id' => $apartamentoLimpieza->id,
                    'tarea_id' => $tarea->id,
                    'status_id' => 3,
                    'fecha_fin' => $hoy
                ]);

                // Notificar al huésped
                \App\Services\GuestCleaningNotificationService::notificar($apartamentoLimpieza);

                // Actualizar fecha_limpieza en la reserva si existe
                $reserva = Reserva::find($apartamentoLimpieza->reserva_id);
                if ($reserva != null) {
                    $reserva->fecha_limpieza = $hoy;
                    $reserva->save();

                    Log::info('Reserva actualizada desde finalizarTarea', [
                        'reserva_id' => $reserva->id,
                        'fecha_limpieza' => $hoy
                ]);
                }

                // DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
                $this->descontarAmenitiesLimpieza($apartamentoLimpieza);

                // Crear alerta si hay observaciones al finalizar la limpieza
                if (!empty($apartamentoLimpieza->observacion)) {
                    $apartamentoNombre = $apartamentoLimpieza->apartamento->nombre ?? 'Apartamento';
                    if ($apartamentoLimpieza->zona_comun_id) {
                        $apartamentoNombre = $apartamentoLimpieza->zonaComun->nombre ?? 'Zona Común';
                    }

                    AlertService::createCleaningObservationAlert(
                        $apartamentoLimpieza->id,
                        $apartamentoNombre,
                        $apartamentoLimpieza->observacion
                    );

                    Log::info('Alerta de observación creada desde finalizarTarea', [
                        'limpieza_id' => $apartamentoLimpieza->id,
                        'apartamento' => $apartamentoNombre
                    ]);
                }
            }

            // Log de la acción
            Log::info('Tarea finalizada', [
                'tarea_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'tipo_tarea' => $tarea->tipoTarea->nombre,
                'fecha_fin_real' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea finalizada correctamente',
                'data' => [
                    'tarea_id' => $tarea->id,
                    'estado' => $tarea->estado,
                    'fecha_fin' => $tarea->fecha_fin_real ? $tarea->fecha_fin_real->format('d/m/Y H:i') : now()->format('d/m/Y H:i')
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error finalizando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Mostrar checklist de una tarea asignada
     */
    public function checklistTarea(TareaAsignada $tarea)
    {
        try {
            // Cargar la relación turno explícitamente
            $tarea->load('turno');

            // Debug: Log de información de la tarea y usuario
            Log::info('Acceso a checklist de tarea', [
                'tarea_id' => $tarea->id,
                'tarea_turno_user_id' => $tarea->turno->user_id,
                'auth_user_id' => Auth::id(),
                'auth_user_role' => Auth::user()->role ?? 'no_role',
                'comparison' => $tarea->turno->user_id === Auth::id()
            ]);

            // Verificar que la tarea pertenece al usuario autenticado
            // Solo verificar si la tarea tiene un turno asociado
            if ($tarea->turno && $tarea->turno->user_id !== Auth::id()) {
                Log::warning('Acceso denegado a tarea', [
                    'tarea_id' => $tarea->id,
                    'tarea_turno_id' => $tarea->turno_id,
                    'tarea_turno_user_id' => $tarea->turno->user_id,
                    'auth_user_id' => Auth::id()
                ]);

                if (request()->expectsJson()) {
                    return response()->json(['error' => 'No autorizado'], 403);
                }
                return redirect()->route('gestion.index')->with('error', 'No tienes autorización para acceder a esta tarea');
            }

            // Cargar relaciones necesarias
            $tarea->load(['tipoTarea', 'apartamento.edificio', 'zonaComun', 'turno.user']);

            // Obtener checklists según el tipo de tarea
            $checklists = collect();
            $itemsExistentes = [];
            $checklistsExistentes = [];

            if ($tarea->apartamento_id) {
                // Checklist de apartamento - Usar funcionalidad completa de gestion/edit
                return $this->checklistTareaApartamento($tarea);
            } elseif ($tarea->zona_comun_id) {
                // Checklist de zona común
                $checklists = \App\Models\ChecklistZonaComun::activos()
                    ->ordenados()
                    ->with(['items.articulo'])
                    ->get();
            } else {
                // Checklist de tarea general
                $checklistGeneral = \App\Models\ChecklistTareaGeneral::activos()
                    ->porCategoria($tarea->tipoTarea->categoria)
                    ->ordenados()
                    ->first();
                if ($checklistGeneral) {
                    $checklists = collect([$checklistGeneral]);
                }
            }

            // Obtener elementos ya completados desde apartamento_limpieza_items
            $elementosCompletados = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)
                ->whereNotNull('item_id')
                ->where('estado', 1)
                ->pluck('item_id')
                ->toArray();

            // Obtener amenities si es apartamento
            $amenities = collect();
            $amenitiesConRecomendaciones = [];
            $consumosExistentes = collect();

            if ($tarea->apartamento_id) {
                $amenities = \App\Models\Amenity::activos()
                    ->orderBy('categoria')
                    ->orderBy('nombre')
                    ->get()
                    ->groupBy('categoria');

                // Obtener consumos existentes (solo si la tabla existe)
                try {
                    $consumosExistentes = \DB::table('consumos_amenities')
                        ->where('tarea_asignada_id', $tarea->id)
                        ->get()
                        ->keyBy('amenity_id');
                } catch (\Exception $e) {
                    // Si la tabla no existe, usar colección vacía
                    $consumosExistentes = collect();
                }

                // Calcular cantidades recomendadas para cada amenity
                foreach ($amenities as $categoria => $amenitiesCategoria) {
                    foreach ($amenitiesCategoria as $amenity) {
                        $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, null, $tarea->apartamento);
                        $consumoExistente = $consumosExistentes->get($amenity->id);

                        $amenitiesConRecomendaciones[$categoria][] = [
                            'amenity' => $amenity,
                            'cantidad_recomendada' => $cantidadRecomendada,
                            'consumo_existente' => $consumoExistente,
                            'stock_disponible' => $amenity->stock_actual
                        ];
                    }
                }

                // Añadir amenities automáticos para niños si la siguiente reserva tiene niños
                $siguienteReserva = $this->obtenerSiguienteReserva($tarea->apartamento->id);
                if ($siguienteReserva && $siguienteReserva->numero_ninos > 0) {
                    $amenitiesNinos = \App\Models\Amenity::paraNinos()->activos()->get();

                    foreach ($amenitiesNinos as $amenityNino) {
                        $cantidadParaNinos = $amenityNino->calcularCantidadParaNinos($siguienteReserva->numero_ninos, $siguienteReserva->edades_ninos ?? []);

                        if ($cantidadParaNinos > 0) {
                            $categoria = $amenityNino->categoria;
                            if (!isset($amenitiesConRecomendaciones[$categoria])) {
                                $amenitiesConRecomendaciones[$categoria] = [];
                            }

                            // Verificar si ya existe este amenity
                            $existe = false;
                            foreach ($amenitiesConRecomendaciones[$categoria] as $amenityExistente) {
                                if ($amenityExistente['amenity']->id === $amenityNino->id) {
                                    $amenityExistente['cantidad_recomendada'] += $cantidadParaNinos;
                                    $amenityExistente['es_automatico_ninos'] = true;
                                    $amenityExistente['motivo_ninos'] = "Automático para {$siguienteReserva->numero_ninos} niño(s)";
                                    $existe = true;
                                    break;
                                }
                            }

                            if (!$existe) {
                                $consumoExistente = $consumosExistentes->get($amenityNino->id);
                                $amenitiesConRecomendaciones[$categoria][] = [
                                    'amenity' => $amenityNino,
                                    'cantidad_recomendada' => $cantidadParaNinos,
                                    'consumo_existente' => $consumoExistente,
                                    'stock_disponible' => $amenityNino->stock_actual,
                                    'es_automatico_ninos' => true,
                                    'motivo_ninos' => "Automático para {$siguienteReserva->numero_ninos} niño(s)"
                                ];
                            }
                        }
                    }
                }
            }

            // Obtener mensaje de amenities del session flash si existe
            $mensajeAmenities = session('mensajeAmenities');

            // Devolver vista específica según el tipo de tarea
            if ($tarea->zona_comun_id) {
                // Para zonas comunes, usar la vista específica
                $zonaComun = $tarea->zonaComun;
                $id = $apartamentoLimpieza->id;
                return view('gestion.edit-zona-comun', compact(
                    'apartamentoLimpieza',
                    'zonaComun',
                    'id',
                    'checklists',
                    'itemsExistentes',
                    'checklistsExistentes'
                ));
            } else {
                // Para apartamentos y tareas generales, usar checklist-tarea
                return view('gestion.checklist-tarea', compact(
                    'tarea',
                    'checklists',
                    'itemsExistentes',
                    'checklistsExistentes',
                    'elementosCompletados',
                    'amenities',
                    'amenitiesConRecomendaciones',
                    'consumosExistentes',
                    'siguienteReserva',
                    'mensajeAmenities'
                ));
            }

        } catch (\Exception $e) {
            Log::error('Error mostrando checklist de tarea: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al cargar el checklist de la tarea');
        }
    }

    /**
     * Actualizar tarea asignada (guardar progreso o finalizar)
     */
    public function updateTarea(Request $request, TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $accion = $request->input('accion', 'guardar');

            if ($accion === 'finalizar') {
                return $this->finalizarTareaChecklist($request, $tarea);
            } else {
                return $this->guardarProgresoTarea($request, $tarea);
            }

        } catch (\Exception $e) {
            Log::error('Error actualizando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar la tarea'], 500);
        }
    }

    /**
     * Guardar progreso de la tarea
     */
    private function guardarProgresoTarea(Request $request, TareaAsignada $tarea)
    {
        try {
            Log::info('Iniciando guardarProgresoTarea', [
                'tarea_id' => $tarea->id,
                'items' => $request->input('items', []),
                'checklist' => $request->input('checklist', []),
                'amenities' => $request->input('amenities', [])
            ]);

            DB::beginTransaction();

            // Obtener items completados del formulario
            $itemsCompletados = $request->input('items', []);
            $checklistsCompletados = $request->input('checklist', []);

            // Limpiar elementos completados existentes
            DB::table('tarea_checklist_completados')
                ->where('tarea_asignada_id', $tarea->id)
                ->delete();

            // Guardar nuevos elementos completados
            foreach ($itemsCompletados as $itemId => $valor) {
                if ($valor == '1') {
                    DB::table('tarea_checklist_completados')->insert([
                        'tarea_asignada_id' => $tarea->id,
                        'item_checklist_id' => $itemId,
                        'completado_por' => Auth::id(),
                        'fecha_completado' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Los checklists se marcan como completados cuando todos sus items están completados
            // No se guardan directamente en la tabla tarea_checklist_completados

            // Guardar amenities si es apartamento (usar sistema original)
            if ($tarea->apartamento_id) {
                $amenities = $request->input('amenities', []);

                // Obtener la limpieza asociada a esta tarea
                $apartamentoLimpieza = ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();

                if ($apartamentoLimpieza) {
                    // IMPORTANTE: Antes de eliminar consumos, reponer el stock que ya fue descontado
                    $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)->get();

                    foreach ($consumosExistentes as $consumoExistente) {
                        $amenity = \App\Models\Amenity::find($consumoExistente->amenity_id);
                        if ($amenity) {
                            // Reponer el stock que fue descontado por este consumo
                            $amenity->reponerStock($consumoExistente->cantidad_consumida);
                            \Log::info("Stock repuesto para amenity {$amenity->id}: +{$consumoExistente->cantidad_consumida} (antes de eliminar consumo ID {$consumoExistente->id})");
                        }
                    }

                    // Ahora sí, eliminar los consumos existentes (el stock ya fue repuesto)
                    \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)->delete();

                    // Guardar nuevos consumos
                    foreach ($amenities as $amenityId => $amenityData) {
                        try {
                            // Validar que amenityId sea válido
                            $amenityId = (int) $amenityId;
                            if ($amenityId <= 0) {
                                \Log::warning("amenityId inválido: {$amenityId}");
                                continue;
                            }

                            // Validar que amenityData sea un array
                            if (!is_array($amenityData)) {
                                \Log::warning("amenityData no es un array para amenity {$amenityId}: " . gettype($amenityData) . " - Valor: " . var_export($amenityData, true));
                                continue;
                            }

                            // Para amenities tipo "por_reserva", usar consumo_por_reserva en lugar de cantidad_dejada
                            $amenity = \App\Models\Amenity::lockForUpdate()->find($amenityId);
                            if ($amenity) {
                                if ($amenity->tipo_consumo === 'por_reserva') {
                                    // Usar consumo_por_reserva configurado
                                    $cantidad = $amenity->consumo_por_reserva ?? 0;
                                } else {
                                    // Para otros tipos, usar cantidad_dejada manual
                                    $cantidad = floatval($amenityData['cantidad_dejada'] ?? 0);
                                }

                                if ($cantidad > 0) {
                                    // Refrescar el modelo para obtener el stock actualizado
                                    $amenity->refresh();

                                    // Descontar stock de forma atómica y registrar consumo con cantidades reales
                                    $resultado = $amenity->descontarStock($cantidad);

                                    // Validar que resultado sea un array
                                    if (!is_array($resultado)) {
                                        \Log::error("descontarStock no retornó un array para amenity {$amenityId}: " . gettype($resultado));
                                        throw new \Exception("Error al descontar stock: resultado inválido");
                                    }

                                    \App\Models\AmenityConsumo::create([
                                        'limpieza_id' => $apartamentoLimpieza->id,
                                        'amenity_id' => $amenityId,
                                        'cantidad_consumida' => $cantidad,
                                        'cantidad_anterior' => $resultado['stock_anterior'],
                                        'cantidad_actual' => $resultado['stock_actual'],
                                        'tipo_consumo' => 'limpieza',
                                        'fecha_consumo' => now()->toDateString(),
                                        'user_id' => auth()->id(),
                                        'reserva_id' => $apartamentoLimpieza->reserva_id,
                                        'apartamento_id' => $apartamentoLimpieza->apartamento_id
                                    ]);

                                    \Log::info("Consumo creado para amenity {$amenityId}: cantidad {$cantidad} (tipo: {$amenity->tipo_consumo}), stock {$resultado['stock_anterior']} -> {$resultado['stock_actual']}");
                                } else {
                                    \Log::warning("Amenity {$amenityId} no encontrado o cantidad <= 0");
                                }
                            } else {
                                \Log::warning("Amenity {$amenityId} no encontrado");
                            }
                        } catch (\Exception $e) {
                            \Log::error("Error procesando amenity {$amenityId}: " . $e->getMessage());
                            \Log::error("Stack trace: " . $e->getTraceAsString());
                            // No hacer throw aquí para que continúe con los demás amenities
                            // Solo loguear el error
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Progreso guardado correctamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando progreso de tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar el progreso'], 500);
        }
    }

    /**
     * Finalizar tarea desde checklist
     */
    private function finalizarTareaChecklist(Request $request, TareaAsignada $tarea)
    {
        try {
            DB::beginTransaction();

            // Guardar progreso primero
            $this->guardarProgresoTarea($request, $tarea);

            // Verificar si necesita consentimiento
            $totalItems = 0;
            $itemsCompletados = 0;

            if ($tarea->apartamento_id) {
                $checklists = \App\Models\Checklist::with(['items'])
                    ->where('edificio_id', $tarea->apartamento->edificio_id)
                    ->get();
            } elseif ($tarea->zona_comun_id) {
                $checklists = \App\Models\ChecklistZonaComun::activos()
                    ->ordenados()
                    ->with(['items'])
                    ->get();
            } else {
                $checklistGeneral = \App\Models\ChecklistTareaGeneral::activos()
                    ->porCategoria($tarea->tipoTarea->categoria)
                    ->ordenados()
                    ->first();
                $checklists = $checklistGeneral ? collect([$checklistGeneral]) : collect();
            }

            foreach ($checklists as $checklist) {
                $totalItems += $checklist->items->count();
            }

            // Obtener items completados desde tarea_checklist_completados (no desde ApartamentoLimpiezaItem)
            $itemsCompletadosIds = \DB::table('tarea_checklist_completados')
                ->where('tarea_asignada_id', $tarea->id)
                ->pluck('item_checklist_id')
                ->toArray();

            $itemsCompletados = count($itemsCompletadosIds);
            $porcentajeCompletado = $totalItems > 0 ? ($itemsCompletados / $totalItems) * 100 : 100;

            // Si no está completo, registrar que se finalizó sin completar (sin bloquear)
            if ($porcentajeCompletado < 100) {
                $tarea->update([
                    'consentimiento_finalizacion' => true,
                    'motivo_consentimiento' => 'Finalizado con checks incompletos (' . $itemsCompletados . '/' . $totalItems . ')',
                    'fecha_consentimiento' => now()->toISOString(),
                ]);
                Log::info('[Limpieza] Finalizada con checks incompletos', [
                    'tarea_id' => $tarea->id,
                    'completados' => $itemsCompletados,
                    'total' => $totalItems,
                    'porcentaje' => round($porcentajeCompletado, 1),
                ]);
            }

            // Marcar tarea como completada
            $tarea->update([
                'estado' => 'completada',
                'fecha_fin_real' => now(),
                'porcentaje_completado' => $porcentajeCompletado
            ]);

            // Buscar y actualizar el ApartamentoLimpieza asociado
            $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
            if (!$apartamentoLimpieza) {
                Log::warning('[Limpieza] ApartamentoLimpieza no encontrada al finalizar, creando...', ['tarea_id' => $tarea->id]);
                $apartamentoLimpieza = $this->crearApartamentoLimpiezaParaTarea($tarea);
            }
            if ($apartamentoLimpieza) {
                $hoy = Carbon::now();
                $apartamentoLimpieza->update([
                    'status_id' => 3, // Limpio
                    'fecha_fin' => $hoy
                ]);

                Log::info('ApartamentoLimpieza actualizado desde finalizarTareaChecklist', [
                    'limpieza_id' => $apartamentoLimpieza->id,
                    'tarea_id' => $tarea->id,
                    'status_id' => 3,
                    'fecha_fin' => $hoy
                ]);

                // Notificar al huésped
                \App\Services\GuestCleaningNotificationService::notificar($apartamentoLimpieza);

                // Actualizar fecha_limpieza en la reserva si existe
                $reserva = Reserva::find($apartamentoLimpieza->reserva_id);
                if ($reserva != null) {
                    $reserva->fecha_limpieza = $hoy;
                    $reserva->save();

                    Log::info('Reserva actualizada desde finalizarTareaChecklist', [
                        'reserva_id' => $reserva->id,
                        'fecha_limpieza' => $hoy
                    ]);
                }

                // DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
                $this->descontarAmenitiesLimpieza($apartamentoLimpieza);

                // Crear alerta si hay observaciones al finalizar la limpieza
                if (!empty($apartamentoLimpieza->observacion)) {
                    $apartamentoNombre = $apartamentoLimpieza->apartamento->nombre ?? 'Apartamento';
                    if ($apartamentoLimpieza->zona_comun_id) {
                        $apartamentoNombre = $apartamentoLimpieza->zonaComun->nombre ?? 'Zona Común';
                    }

                    AlertService::createCleaningObservationAlert(
                        $apartamentoLimpieza->id,
                        $apartamentoNombre,
                        $apartamentoLimpieza->observacion
                    );

                    Log::info('Alerta de observación creada desde finalizarTareaChecklist', [
                        'limpieza_id' => $apartamentoLimpieza->id,
                        'apartamento' => $apartamentoNombre
                    ]);
                }
            }

            // Crear nueva tarea si es necesario (para tareas recurrentes)
            if ($tarea->tipoTarea->es_recurrente) {
                $this->crearTareaRecurrente($tarea);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tarea finalizada correctamente',
                'porcentaje_completado' => $porcentajeCompletado
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error finalizando tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error al finalizar la tarea'], 500);
        }
    }

    /**
     * Crear tarea recurrente
     */
    private function crearTareaRecurrente(TareaAsignada $tarea)
    {
        try {
            $fechaSiguiente = $this->calcularFechaSiguienteTarea($tarea);

            if ($fechaSiguiente) {
                TareaAsignada::create([
                    'turno_id' => $tarea->turno_id,
                    'tipo_tarea_id' => $tarea->tipo_tarea_id,
                    'apartamento_id' => $tarea->apartamento_id,
                    'zona_comun_id' => $tarea->zona_comun_id,
                    'fecha_asignada' => $fechaSiguiente,
                    'estado' => 'pendiente',
                    'prioridad_calculada' => $tarea->tipoTarea->prioridad_base,
                    'orden_ejecucion' => $tarea->orden_ejecucion
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error creando tarea recurrente: ' . $e->getMessage());
        }
    }

    /**
     * Checklist de tarea para apartamento - Usa funcionalidad completa de gestion/edit
     */
    private function checklistTareaApartamento(TareaAsignada $tarea)
    {
        $apartamento = $tarea->apartamento;
        $edificioId = $apartamento->edificio_id;

        // Obtener checklists con items y artículos (igual que gestion/edit)
        $checklists = Checklist::with(['items.articulo'])->where('edificio_id', $edificioId)->get();

        // Obtener o crear ApartamentoLimpieza real para esta tarea
        $apartamentoLimpieza = ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();

        Log::info('Buscando ApartamentoLimpieza existente', [
            'tarea_id' => $tarea->id,
            'limpieza_encontrada' => $apartamentoLimpieza ? $apartamentoLimpieza->id : 'null'
        ]);

        if (!$apartamentoLimpieza) {
            // Si no existe, crear uno nuevo
            Log::info('No se encontró ApartamentoLimpieza, creando nuevo');
            $apartamentoLimpieza = $this->crearApartamentoLimpiezaParaTarea($tarea);
        } else {
            Log::info('ApartamentoLimpieza existente encontrado', [
                'limpieza_id' => $apartamentoLimpieza->id,
                'tarea_asignada_id' => $apartamentoLimpieza->tarea_asignada_id
            ]);
        }

        // Cargar relación apartamento
        $apartamentoLimpieza->load('apartamento');

        // Obtener items marcados para esta tarea
        $item_check = \DB::table('tarea_checklist_completados')
            ->where('tarea_asignada_id', $tarea->id)
            ->get();
        $itemsExistentes = $item_check->pluck('estado', 'item_checklist_id')->toArray();
        $checklist_check = $item_check->whereNotNull('checklist_id');
        $checklistsExistentes = $checklist_check->pluck('estado', 'checklist_id')->toArray();

        // TODO: Verificar fotos cuando la tabla fotos_limpieza esté disponible
        // Por ahora, no verificamos fotos para evitar errores

        // Obtener amenities para esta limpieza (igual que gestion/edit)
        $amenities = \App\Models\Amenity::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Obtener consumos existentes para esta limpieza (usar tabla del sistema original)
        $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
            ->with('amenity')
            ->get()
            ->keyBy('amenity_id');

        // Calcular cantidades recomendadas para cada amenity (igual que gestion/edit)
        $amenitiesConRecomendaciones = [];
        foreach ($amenities as $categoria => $amenitiesCategoria) {
            foreach ($amenitiesCategoria as $amenity) {
                $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, null, $apartamento);
                $consumoExistente = $consumosExistentes->get($amenity->id);

                $amenitiesConRecomendaciones[$categoria][] = [
                    'amenity' => $amenity,
                    'cantidad_recomendada' => $cantidadRecomendada,
                    'consumo_existente' => $consumoExistente,
                    'stock_disponible' => $amenity->stock_actual
                ];
            }
        }

        // Añadir amenities automáticos para niños si la siguiente reserva tiene niños
        $siguienteReserva = $this->obtenerSiguienteReserva($apartamento->id);
        if ($siguienteReserva && $siguienteReserva->numero_ninos > 0) {
            $amenitiesNinos = \App\Models\Amenity::paraNinos()->activos()->get();

            foreach ($amenitiesNinos as $amenityNino) {
                $cantidadParaNinos = $amenityNino->calcularCantidadParaNinos($siguienteReserva->numero_ninos, $siguienteReserva->edades_ninos ?? []);

                if ($cantidadParaNinos > 0) {
                    $categoria = $amenityNino->categoria;
                    if (!isset($amenitiesConRecomendaciones[$categoria])) {
                        $amenitiesConRecomendaciones[$categoria] = [];
                    }

                    // Verificar si ya existe este amenity
                    $existe = false;
                    foreach ($amenitiesConRecomendaciones[$categoria] as $amenityExistente) {
                        if ($amenityExistente['amenity']->id === $amenityNino->id) {
                            $amenityExistente['cantidad_recomendada'] += $cantidadParaNinos;
                            $amenityExistente['es_automatico_ninos'] = true;
                            $amenityExistente['motivo_ninos'] = "Automático para {$siguienteReserva->numero_ninos} niño(s)";
                            $existe = true;
                            break;
                        }
                    }

                    if (!$existe) {
                        $consumoExistente = $consumosExistentes->get($amenityNino->id);
                        $amenitiesConRecomendaciones[$categoria][] = [
                            'amenity' => $amenityNino,
                            'cantidad_recomendada' => $cantidadParaNinos,
                            'consumo_existente' => $consumoExistente,
                            'stock_disponible' => $amenityNino->stock_actual,
                            'es_automatico_ninos' => true,
                            'motivo_ninos' => "Automático para {$siguienteReserva->numero_ninos} niño(s)"
                        ];
                    }
                }
            }
        }

        // Obtener mensaje de amenities del session flash si existe
        $mensajeAmenities = session('mensajeAmenities');

        // Artículos activos para el modal simple de reposición 1:1 (mostrar todos, incluso con stock 0)
        $articulosActivos = \App\Models\Articulo::activos()
            ->orderBy('nombre')
            ->get();

        // Usar la vista de gestion/edit pero adaptada para tareas
        return view('gestion.edit-tarea', compact(
            'tarea',
            'apartamentoLimpieza',
            'apartamento',
            'checklists',
            'itemsExistentes',
            'checklistsExistentes',
            'amenitiesConRecomendaciones',
            'siguienteReserva',
            'mensajeAmenities',
            'articulosActivos'
        ));
    }

    /**
     * Crear ApartamentoLimpieza real para una tarea de apartamento
     */
    private function crearApartamentoLimpiezaParaTarea(TareaAsignada $tarea)
    {
        try {
            // Verificar si ya existe una limpieza para esta tarea
            $limpiezaExistente = ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();

            if ($limpiezaExistente) {
                return $limpiezaExistente;
            }

            // Determinar tipo de limpieza
            $tipoLimpieza = $tarea->apartamento_id ? 'apartamento' : 'zona_comun';

            // Crear nueva limpieza
            $limpieza = ApartamentoLimpieza::create([
                'apartamento_id' => $tarea->apartamento_id,
                'zona_comun_id' => $tarea->zona_comun_id,
                'empleada_id' => $tarea->turno->user_id,
                'tipo_limpieza' => $tipoLimpieza,
                'status_id' => 1, // En progreso
                'fecha_comienzo' => now(),
                'tarea_asignada_id' => $tarea->id, // Relación con la tarea
                'origen' => 'tarea_asignada'
            ]);

            Log::info('ApartamentoLimpieza creado para tarea', [
                'tarea_id' => $tarea->id,
                'limpieza_id' => $limpieza->id,
                'apartamento_id' => $tarea->apartamento_id,
                'zona_comun_id' => $tarea->zona_comun_id,
                'tipo_limpieza' => $tipoLimpieza,
                'empleada_id' => $tarea->turno->user_id
            ]);

            return $limpieza;

        } catch (\Exception $e) {
            Log::error('Error creando ApartamentoLimpieza para tarea: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualizar estado de un item del checklist
     */
    public function updateChecklistTarea(Request $request, TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            $itemId = $request->input('item_id');
            $completado = $request->input('completado', false);

            if ($completado) {
                // Marcar como completado
                \DB::table('tarea_checklist_completados')->updateOrInsert(
                    [
                        'tarea_asignada_id' => $tarea->id,
                        'item_checklist_id' => $itemId
                    ],
                    [
                        'completado_por' => Auth::id(),
                        'fecha_completado' => now(),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]
                );
            } else {
                // Marcar como no completado
                \DB::table('tarea_checklist_completados')
                    ->where('tarea_asignada_id', $tarea->id)
                    ->where('item_checklist_id', $itemId)
                    ->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando checklist de tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Finalizar checklist y completar tarea
     */
    public function finalizarChecklistTarea(Request $request, TareaAsignada $tarea)
    {
        try {
            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno->user_id !== Auth::id()) {
                return response()->json(['error' => 'No autorizado'], 403);
            }

            // Verificar que la tarea está en progreso
            if ($tarea->estado !== 'en_progreso') {
                return response()->json(['error' => 'La tarea no está en estado en progreso'], 400);
            }

            // Obtener items completados
            $itemsCompletados = \DB::table('tarea_checklist_completados')
                ->where('tarea_asignada_id', $tarea->id)
                ->count();

            // Obtener total de items del checklist
            $totalItems = 0;
            if ($tarea->apartamento_id) {
                $apartamento = $tarea->apartamento;
                if ($apartamento && $apartamento->edificio && is_object($apartamento->edificio) && $apartamento->edificio->checklist) {
                    $totalItems = $apartamento->edificio->checklist->items()->activos()->count();
                }
            } elseif ($tarea->zona_comun_id) {
                $checklist = \App\Models\ChecklistZonaComun::activos()->ordenados()->first();
                if ($checklist) {
                    $totalItems = $checklist->items()->activos()->count();
                }
            } else {
                $checklist = \App\Models\ChecklistTareaGeneral::activos()
                    ->porCategoria($tarea->tipoTarea->categoria)
                    ->ordenados()
                    ->first();
                if ($checklist) {
                    $totalItems = $checklist->items()->activos()->count();
                }
            }

            // Actualizar estado de la tarea
            $tarea->update([
                'estado' => 'completada',
                'fecha_fin_real' => now(),
                'observaciones' => $request->input('observaciones', '')
            ]);

            // Buscar y actualizar el ApartamentoLimpieza asociado
            $apartamentoLimpieza = \App\Models\ApartamentoLimpieza::where('tarea_asignada_id', $tarea->id)->first();
            if ($apartamentoLimpieza) {
                $hoy = Carbon::now();
                $apartamentoLimpieza->update([
                    'status_id' => 3, // Limpio
                    'fecha_fin' => $hoy
                ]);

                Log::info('ApartamentoLimpieza actualizado desde finalizarChecklistTarea', [
                    'limpieza_id' => $apartamentoLimpieza->id,
                    'tarea_id' => $tarea->id,
                    'status_id' => 3,
                    'fecha_fin' => $hoy
                ]);

                // Notificar al huésped
                \App\Services\GuestCleaningNotificationService::notificar($apartamentoLimpieza);

                // Actualizar fecha_limpieza en la reserva si existe
                $reserva = Reserva::find($apartamentoLimpieza->reserva_id);
                if ($reserva != null) {
                    $reserva->fecha_limpieza = $hoy;
                    $reserva->save();

                    Log::info('Reserva actualizada desde finalizarChecklistTarea', [
                        'reserva_id' => $reserva->id,
                        'fecha_limpieza' => $hoy
                ]);
                }

                // DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
                $this->descontarAmenitiesLimpieza($apartamentoLimpieza);

                // Crear alerta si hay observaciones al finalizar la limpieza
                if (!empty($apartamentoLimpieza->observacion)) {
                    $apartamentoNombre = $apartamentoLimpieza->apartamento->nombre ?? 'Apartamento';
                    if ($apartamentoLimpieza->zona_comun_id) {
                        $apartamentoNombre = $apartamentoLimpieza->zonaComun->nombre ?? 'Zona Común';
                    }

                    AlertService::createCleaningObservationAlert(
                        $apartamentoLimpieza->id,
                        $apartamentoNombre,
                        $apartamentoLimpieza->observacion
                    );

                    Log::info('Alerta de observación creada desde finalizarChecklistTarea', [
                        'limpieza_id' => $apartamentoLimpieza->id,
                        'apartamento' => $apartamentoNombre
                    ]);
                }
            }

            // Log de la acción
            Log::info('Tarea completada con checklist', [
                'tarea_id' => $tarea->id,
                'usuario_id' => Auth::id(),
                'tipo_tarea' => $tarea->tipoTarea->nombre,
                'items_completados' => $itemsCompletados,
                'total_items' => $totalItems,
                'fecha_fin_real' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tarea completada correctamente',
                'data' => [
                    'tarea_id' => $tarea->id,
                    'estado' => $tarea->estado,
                    'fecha_fin' => $tarea->fecha_fin->format('d/m/Y H:i'),
                    'items_completados' => $itemsCompletados,
                    'total_items' => $totalItems
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error finalizando checklist de tarea: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create_fondo($id)
    {

        $apartamentoLimpio = ApartamentoLimpieza::where('fecha_fin', null)
            ->where('apartamento_id', explode(' - ', $id)[1])
            ->whereDate('fecha_comienzo', \Carbon\Carbon::today())
            ->first();
        $reserva = Reserva::find($id);
            if ($reserva == null) {
                $apartamentoId = explode(' - ', $id)[1];
                $id = null;
            } else {
                $apartamentoId = $reserva->apartamento_id;
            }
            if ($apartamentoLimpio == null) {
                // Verificar que el usuario autenticado está activo
                $usuarioActual = Auth::user();
                if ($usuarioActual->inactive) {
                    Alert::error('Error', 'No se puede crear la limpieza: el usuario está inactivo');
                    return redirect()->route('gestion.index');
                }

                $apartamentoLimpieza = ApartamentoLimpieza::create([
                    'apartamento_id' => $apartamentoId,
                    'fecha_comienzo' => Carbon::now(),
                    'status_id' => 2,
                    'reserva_id' => $id,
                    'user_id' => $usuarioActual->id
                ]);
                $apartamentoLimpieza->save();
                if ($reserva != null) {
                    $reserva->fecha_limpieza = Carbon::now();
                    $reserva->save();
                }
            } else {
                $apartamentoLimpieza = $apartamentoLimpio;
            }




        // Verificar que el apartamento existe
        $apartamento = Apartamento::find($apartamentoId);
        if (!$apartamento) {
            abort(404, 'Apartamento no encontrado');
        }

        $edificioId = $apartamento->edificio_id;

        // Verificar que el edificio existe
        if (!$edificioId) {
            abort(404, 'Edificio no encontrado para este apartamento');
        }

        $checklists = Checklist::with('items')->where('edificio_id', $edificioId)->get();
        $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
        $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();

        // Obtener amenities para esta limpieza
        $amenities = \App\Models\Amenity::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Obtener consumos existentes para esta limpieza
        $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
            ->with('amenity')
            ->get()
            ->keyBy('amenity_id');

        // Calcular cantidades recomendadas para cada amenity
        $amenitiesConRecomendaciones = [];
        foreach ($amenities as $categoria => $amenitiesCategoria) {
            foreach ($amenitiesCategoria as $amenity) {
                $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, $reserva, $apartamentoLimpieza->apartamento);
                $consumoExistente = $consumosExistentes->get($amenity->id);

                $amenitiesConRecomendaciones[$categoria][] = [
                    'amenity' => $amenity,
                    'cantidad_recomendada' => $cantidadRecomendada,
                    'consumo_existente' => $consumoExistente,
                    'stock_disponible' => $amenity->stock_actual
                ];
            }
        }

        // Artículos activos para el modal simple de reposición 1:1 (mostrar todos, incluso con stock 0)
        $articulosActivos = \App\Models\Articulo::activos()
            ->orderBy('nombre')
            ->get();

        return view('gestion.edit', compact(
            'apartamentoLimpieza',
            'id',
            'checklists',
            'itemsExistentes',
            'amenitiesConRecomendaciones',
            'consumosExistentes',
            'articulosActivos'
        ));
    }

    public function create($id)
    {
        if (isset(explode(' - ', $id)[1])) {
            return redirect()->route('gestion.create_fondo', $id);
        } else {

        $reserva = Reserva::find($id);
        if (!$reserva) {
            Alert::error('Error', 'Reserva no encontrada');
            return redirect()->route('gestion.index');
        }

        $apartamentoLimpio = ApartamentoLimpieza::where('fecha_fin', null)
            ->where('apartamento_id', $reserva->apartamento_id)
            ->whereDate('fecha_comienzo', \Carbon\Carbon::today())
            ->first();

        // Buscar y actualizar la tarea asignada relacionada con esta reserva
        $usuarioActual = Auth::user();
        $tareaAsignada = null;

        // Buscar la tarea asignada del usuario actual para este apartamento
        // Buscar turno de hoy: programado, en_progreso (cuando ya activó el turno) o activo
        $turnoActivo = \App\Models\TurnoTrabajo::where('user_id', $usuarioActual->id)
            ->where('fecha', Carbon::today())
            ->whereIn('estado', ['activo', 'programado', 'en_progreso'])
            ->first();

        if ($turnoActivo) {
            // Buscar la tarea asignada para este apartamento en el turno activo
            $tareaAsignada = TareaAsignada::where('turno_id', $turnoActivo->id)
                ->where('apartamento_id', $reserva->apartamento_id)
                ->whereIn('estado', ['pendiente', null])
                ->first();

            // Si encontramos la tarea y está pendiente, actualizarla a "en_progreso"
            if ($tareaAsignada && ($tareaAsignada->estado === 'pendiente' || $tareaAsignada->estado === null)) {
                $tareaAsignada->estado = 'en_progreso';
                $tareaAsignada->fecha_inicio_real = $tareaAsignada->fecha_inicio_real ?? now();
                $tareaAsignada->save();

                Log::info('Tarea actualizada a "en_progreso" al acceder a la limpieza', [
                    'tarea_id' => $tareaAsignada->id,
                    'reserva_id' => $id,
                    'apartamento_id' => $reserva->apartamento_id,
                    'usuario_id' => $usuarioActual->id,
                    'estado_anterior' => $tareaAsignada->getOriginal('estado')
                ]);
            }
        }

        if ($apartamentoLimpio == null) {
            // Verificar que el usuario autenticado está activo
            if ($usuarioActual->inactive) {
                Alert::error('Error', 'No se puede crear la limpieza: el usuario está inactivo');
                return redirect()->route('gestion.index');
            }

            $apartamentoLimpieza = ApartamentoLimpieza::create([
                'apartamento_id' => $reserva->apartamento_id,
                'fecha_comienzo' => Carbon::now(),
                'status_id' => 2,
                'reserva_id' => $id,
                'user_id' => $usuarioActual->id,
                'tarea_asignada_id' => $tareaAsignada ? $tareaAsignada->id : null
            ]);
            $reserva->fecha_limpieza = Carbon::now();
            $reserva->save();
        } else {
            $apartamentoLimpieza = $apartamentoLimpio;

            // Si la limpieza ya existe pero no tiene tarea_asignada_id, actualizarla
            if (!$apartamentoLimpieza->tarea_asignada_id && $tareaAsignada) {
                $apartamentoLimpieza->tarea_asignada_id = $tareaAsignada->id;
                $apartamentoLimpieza->save();
            }
        }
        $apartamentoId = $reserva->apartamento_id;

        // Verificar que el apartamento existe
        $apartamento = Apartamento::find($apartamentoId);
        if (!$apartamento) {
            abort(404, 'Apartamento no encontrado');
        }

        $edificioId = $apartamento->edificio_id;

        // Verificar que el edificio existe
        if (!$edificioId) {
            abort(404, 'Edificio no encontrado para este apartamento');
        }

        $checklists = Checklist::with('items')->where('edificio_id', $edificioId)->get();
        $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
        $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();

        // Obtener amenities para esta limpieza
        $amenities = \App\Models\Amenity::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');

        // Obtener consumos existentes para esta limpieza
        $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
            ->with('amenity')
            ->get()
            ->keyBy('amenity_id');

        // Calcular cantidades recomendadas para cada amenity
        $amenitiesConRecomendaciones = [];
        foreach ($amenities as $categoria => $amenitiesCategoria) {
            foreach ($amenitiesCategoria as $amenity) {
                $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, $reserva, $apartamentoLimpieza->apartamento);
                $consumoExistente = $consumosExistentes->get($amenity->id);

                $amenitiesConRecomendaciones[$categoria][] = [
                    'amenity' => $amenity,
                    'cantidad_recomendada' => $cantidadRecomendada,
                    'consumo_existente' => $consumoExistente,
                    'stock_disponible' => $amenity->stock_actual
                ];
            }
        }

        return view('gestion.edit', compact(
            'apartamentoLimpieza',
            'id',
            'checklists',
            'itemsExistentes',
            'amenitiesConRecomendaciones',
            'consumosExistentes'
        ));
    }
    }


    public function store(Request $request)
    {
        $id = $request->id;
        $apartamento = ApartamentoLimpieza::find($id);

        if (!$apartamento) {
            Alert::error('Error', 'Apartamento no encontrado');
            return redirect()->route('gestion.index');
        }

        // Eliminar registros anteriores para este apartamento y limpieza
        ApartamentoLimpiezaItem::where('id_limpieza', $apartamento->id)->delete();

        // Guardar los nuevos ítems marcados en el formulario
        if ($request->has('items')) {
            foreach ($request->items as $itemId => $estado) {
                ApartamentoLimpiezaItem::create([
                    'id_limpieza' => $apartamento->id,
                    'id_reserva' => $apartamento->reserva_id,
                    'item_id' => $itemId,
                    'estado' => $estado == 1 ? 1 : 0,
                ]);
            }
            foreach ($request->checklist as $checklistId => $estado) {
                ApartamentoLimpiezaItem::create([
                    'id_limpieza' => $apartamento->id,
                    'id_reserva' => $apartamento->reserva_id,
                    'estado' => $estado == 1 ? 1 : 0,
                    'checklist_id' => $checklistId
                ]);
            }
        }

        // Guardar observación
        $apartamento->observacion = $request->observacion;

        // Asignar el usuario si no existe
        if (empty($apartamento->user_id)) {
            $apartamento->user_id = Auth::user()->id;
        }

        $apartamento->save();

        Alert::success('Guardado con Éxito', 'Apartamento actualizado correctamente');
        return redirect()->route('gestion.index');
    }


    /**
     * Display the specified resource.
     */
    public function storeColumn(Request $request)
    {
        $apartamento = ApartamentoLimpieza::find($request->id);

        if ($apartamento) {
            $columna = $request->name;
            $apartamento->$columna = $request->checked == 'true' ? true : false;
            $apartamento->save();
            Alert::toast('Actualizado', 'success');
            return true;

        }
        Alert::toast('Error, intentelo mas tarde', 'error');

        return false;
    }

    /**
     * Display the specified resource.
     */
    public function show(GestionApartamento $gestionApartamento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApartamentoLimpieza $apartamentoLimpieza)
    {
        // Verificar que el apartamentoLimpieza existe y tiene los datos necesarios
        if (!$apartamentoLimpieza) {
            abort(404, 'Limpieza no encontrada');
        }

        $id = $apartamentoLimpieza->id;

        // Debug temporal - ver en consola del navegador
        if (app()->environment('local')) {
            error_log("Debug ApartamentoLimpieza ID: {$apartamentoLimpieza->id}, Tipo: {$apartamentoLimpieza->tipo_limpieza}, ZonaComunID: {$apartamentoLimpieza->zona_comun_id}, ApartamentoID: {$apartamentoLimpieza->apartamento_id}");
        }

        // Determinar si es una zona común o un apartamento
        if ($apartamentoLimpieza->tipo_limpieza === 'zona_comun') {
            // Es una zona común
            $zonaComun = $apartamentoLimpieza->zonaComun;
            if (!$zonaComun) {
                abort(404, 'Zona común no encontrada');
            }

            // Obtener checklists específicos para zonas comunes
            $checklists = \App\Models\ChecklistZonaComun::activos()->ordenados()->with(['items.articulo'])->get();

            // Obtener items marcados para esta limpieza
            $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
            $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();
            $checklist_check = $item_check->whereNotNull('checklist_zona_comun_id')->filter(function ($item) {
                return $item->estado == 1;
            });
            $checklistsExistentes = $checklist_check->pluck('estado', 'checklist_zona_comun_id')->toArray();

            return view('gestion.edit-zona-comun', compact(
                'apartamentoLimpieza',
                'zonaComun',
                'id',
                'checklists',
                'itemsExistentes',
                'checklistsExistentes'
            ));

        } else {
            // Es un apartamento
            $apartamentoId = $apartamentoLimpieza->apartamento_id;

            // Verificar que el apartamento existe
            $apartamento = Apartamento::find($apartamentoId);
            if (!$apartamento) {
                abort(404, 'Apartamento no encontrado');
            }

            $edificioId = $apartamento->edificio_id;

            // Verificar que el edificio existe
            if (!$edificioId) {
                abort(404, 'Edificio no encontrado para este apartamento');
            }

            $checklists = Checklist::with(['items.articulo'])->where('edificio_id', $edificioId)->get();
            $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
            $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();
            $checklist_check = $item_check->whereNotNull('checklist_id')->filter(function ($item) {
                return $item->estado == 1;
            });

            $checklistsExistentes = $checklist_check->pluck('estado', 'checklist_id')->toArray();

            // Obtener amenities para esta limpieza
            $amenities = \App\Models\Amenity::activos()
                ->orderBy('categoria')
                ->orderBy('nombre')
                ->get()
                ->groupBy('categoria');

            // Obtener consumos existentes para esta limpieza
            $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
                ->with('amenity')
                ->get()
                ->keyBy('amenity_id');

            // Calcular cantidades recomendadas para cada amenity
            $amenitiesConRecomendaciones = [];
            foreach ($amenities as $categoria => $amenitiesCategoria) {
                foreach ($amenitiesCategoria as $amenity) {
                    $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, $apartamentoLimpieza->origenReserva, $apartamentoLimpieza->apartamento);
                    $consumoExistente = $consumosExistentes->get($amenity->id);

                    $amenitiesConRecomendaciones[$categoria][] = [
                        'amenity' => $amenity,
                        'cantidad_recomendada' => $cantidadRecomendada,
                        'consumo_existente' => $consumoExistente,
                        'stock_disponible' => $amenity->stock_actual
                    ];
                }
            }

            // Añadir amenities automáticos para niños si la siguiente reserva tiene niños
            $siguienteReserva = $this->obtenerSiguienteReserva($apartamento->id);
            if ($siguienteReserva && $siguienteReserva->numero_ninos > 0) {
                $amenitiesNinos = \App\Models\Amenity::paraNinos()->activos()->get();

                foreach ($amenitiesNinos as $amenityNino) {
                    $cantidadParaNinos = $amenityNino->calcularCantidadParaNinos($siguienteReserva->numero_ninos, $siguienteReserva->edades_ninos ?? []);

                    if ($cantidadParaNinos > 0) {
                        $categoria = $amenityNino->categoria;
                        if (!isset($amenitiesConRecomendaciones[$categoria])) {
                            $amenitiesConRecomendaciones[$categoria] = [];
                        }

                        // Verificar si ya existe este amenity
                        $existe = false;
                        foreach ($amenitiesConRecomendaciones[$categoria] as $amenityExistente) {
                            if ($amenityExistente['amenity']->id === $amenityNino->id) {
                                $amenityExistente['cantidad_recomendada'] += $cantidadParaNinos;
                                $amenityExistente['es_automatico_ninos'] = true;
                                $amenityExistente['motivo_ninos'] = "Automático para {$siguienteReserva->numero_ninos} niño(s)";
                                $existe = true;
                                break;
                            }
                        }

                        if (!$existe) {
                            $consumoExistente = $consumosExistentes->get($amenityNino->id);
                            $amenitiesConRecomendaciones[$categoria][] = [
                                'amenity' => $amenityNino,
                                'cantidad_recomendada' => $cantidadParaNinos,
                                'consumo_existente' => $consumoExistente,
                                'stock_disponible' => $amenityNino->stock_actual,
                                'es_automatico_ninos' => true,
                                'motivo_ninos' => "Automático para {$siguienteReserva->numero_ninos} niño(s)"
                            ];
                        }
                    }
                }
            }

            // Obtener mensaje de amenities del session flash si existe
            $mensajeAmenities = session('mensajeAmenities');

            return view('gestion.edit', compact(
                'apartamentoLimpieza',
                'id',
                'checklists',
                'itemsExistentes',
                'checklistsExistentes',
                'amenitiesConRecomendaciones',
                'consumosExistentes',
                'mensajeAmenities'
            ));
        }
    }

    /**
     * Obtener la siguiente reserva para un apartamento
     */
    private function obtenerSiguienteReserva($apartamentoId)
    {
        return \App\Models\Reserva::where('apartamento_id', $apartamentoId)
            ->where('fecha_entrada', '>', now()->toDateString())
            ->where(function($query) {
                $query->where('estado_id', '!=', 4)
                      ->orWhereNull('estado_id');
            })
            ->orderBy('fecha_entrada', 'asc')
            ->select('id', 'apartamento_id', 'fecha_entrada', 'fecha_salida', 'numero_personas', 'numero_ninos', 'edades_ninos', 'notas_ninos', 'codigo_reserva')
            ->first();
    }


    public function update(Request $request, ApartamentoLimpieza $apartamentoLimpieza)
{
    // Eliminar ítems anteriores para este registro
    ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->delete();

    // Guardar nuevos ítems desde los checkboxes de ítems
    if ($request->has('items')) {
        foreach ($request->items as $itemId => $estado) {
            ApartamentoLimpiezaItem::create([
                'id_limpieza'   => $apartamentoLimpieza->id,
                'id_reserva'    => $apartamentoLimpieza->reserva_id,
                'item_id'       => $itemId,
                'estado'        => $estado == 1 ? 1 : 0,
            ]);
        }
    }

    // Guardar nuevos ítems desde los checkboxes de checklist
    if ($request->has('checklist')) {
        foreach ($request->checklist as $checklistId => $estado) {
            ApartamentoLimpiezaItem::create([
                'id_limpieza'   => $apartamentoLimpieza->id,
                'id_reserva'    => $apartamentoLimpieza->reserva_id,
                'checklist_id'  => $checklistId,
                'estado'        => $estado == 1 ? 1 : 0,
            ]);
        }
    }

    // Guardar amenities de consumo
    if ($request->has('amenities')) {
        // Debug: Log de los datos recibidos
        Log::info('Amenities recibidos:', $request->amenities);

        $amenitiesGuardados = 0;
        $amenitiesCreados = 0;
        $amenitiesActualizados = 0;

        foreach ($request->amenities as $amenityId => $amenityData) {
            try {
                // Validar datos antes de insertar
                $cantidadDejada = intval($amenityData['cantidad_dejada'] ?? 0);
                $observaciones = $amenityData['observaciones'] ?? null;

                // Solo procesar si hay cantidad dejada
                if ($cantidadDejada > 0) {
                    // Buscar si ya existe un consumo para este amenity
                    $consumoExistente = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
                        ->where('amenity_id', $amenityId)
                        ->first();

                    if ($consumoExistente) {
                        // ACTUALIZAR el consumo existente
                        // Usar lockForUpdate para evitar condiciones de carrera
                        $amenity = \App\Models\Amenity::lockForUpdate()->find($amenityId);
                        if (!$amenity) {
                            \Log::error("No se pudo encontrar el amenity {$amenityId} para actualizar stock");
                            continue;
                        }

                        // Refrescar el modelo para obtener el stock más reciente
                        $amenity->refresh();

                        // Stock antes del ajuste
                        $stockAnterior = $amenity->stock_actual;
                        $cantidadConsumoAnterior = $consumoExistente->cantidad_consumida;

                        // Ajustar el stock basado en la diferencia de consumo
                        \Log::info("ANTES de ajustar stock - Amenity {$amenityId}: stock_actual = {$stockAnterior}, consumo anterior = {$cantidadConsumoAnterior}, consumo nuevo = {$cantidadDejada}");

                        $stockActual = $amenity->ajustarStock($cantidadConsumoAnterior, $cantidadDejada);

                        \Log::info("DESPUÉS de ajustar stock - Amenity {$amenityId}: stock_actual = {$stockActual}");
                        \Log::info("Stock del amenity {$amenityId} ajustado: diferencia " . ($cantidadDejada - $cantidadConsumoAnterior) . " (de {$cantidadConsumoAnterior} a {$cantidadDejada})");

                        // Actualizar el consumo con los valores reales del stock
                        $consumoExistente->update([
                            'cantidad_consumida' => $cantidadDejada,
                            'cantidad_anterior' => $stockAnterior,
                            'cantidad_actual' => $stockActual,
                            'observaciones' => $observaciones,
                            'fecha_consumo' => now()
                        ]);

                            // Verificar si el stock está bajo después del ajuste
                            if ($amenity->verificarStockBajo()) {
                                Alert::warning('Stock Bajo', "El amenity '{$amenity->nombre}' tiene stock bajo (actual: {$amenity->stock_actual})");
                        }

                        $amenitiesActualizados++;
                        \Log::info("Amenity {$amenityId} ACTUALIZADO con cantidad {$cantidadDejada} (stock: {$stockAnterior} -> {$stockActual})");
                    } else {
                        // CREAR nuevo consumo solo si no existe: descontar stock y registrar con cantidades reales
                        // Usar lockForUpdate para evitar condiciones de carrera
                        $amenity = \App\Models\Amenity::lockForUpdate()->find($amenityId);
                        if ($amenity) {
                            try {
                                // Refrescar el modelo para obtener el stock más reciente
                                $amenity->refresh();

                                \Log::info("ANTES de descontar stock - Amenity {$amenityId}: stock_actual = {$amenity->stock_actual}");
                                $resultadoDescuento = $amenity->descontarStock($cantidadDejada);

                                // Validar que el resultado sea un array
                                if (!is_array($resultadoDescuento)) {
                                    \Log::error("Error: descontarStock no devolvió un array para amenity {$amenityId}. Tipo: " . gettype($resultadoDescuento) . ", Valor: " . var_export($resultadoDescuento, true));
                                    throw new \Exception("Error al descontar stock: resultado inválido del método descontarStock()");
                                }

                                // Validar que tenga las claves necesarias
                                if (!isset($resultadoDescuento['stock_anterior']) || !isset($resultadoDescuento['stock_actual'])) {
                                    \Log::error("Error: descontarStock no devolvió las claves esperadas para amenity {$amenityId}. Claves: " . implode(', ', array_keys($resultadoDescuento)));
                                    throw new \Exception("Error al descontar stock: resultado incompleto del método descontarStock()");
                                }

                                \Log::info("DESPUÉS de descontar stock - Amenity {$amenityId}: stock_actual = {$resultadoDescuento['stock_actual']}");
                                \Log::info("Stock del amenity {$amenityId} descontado: -{$cantidadDejada} (nuevo consumo)");

                                $nuevoConsumo = \App\Models\AmenityConsumo::create([
                                    'amenity_id' => $amenityId,
                                    'limpieza_id' => $apartamentoLimpieza->id,
                                    'reserva_id' => $apartamentoLimpieza->reserva_id,
                                    'apartamento_id' => $apartamentoLimpieza->apartamento_id,
                                    'user_id' => auth()->id(),
                                    'tipo_consumo' => 'limpieza',
                                    'cantidad_consumida' => $cantidadDejada,
                                    'cantidad_anterior' => $resultadoDescuento['stock_anterior'],
                                    'cantidad_actual' => $resultadoDescuento['stock_actual'],
                                    'costo_unitario' => 0,
                                    'costo_total' => 0,
                                    'observaciones' => $observaciones,
                                    'fecha_consumo' => now()
                                ]);

                                // Verificar si el stock está bajo después del descuento
                                if ($amenity->verificarStockBajo()) {
                                    Alert::warning('Stock Bajo', "El amenity '{$amenity->nombre}' tiene stock bajo (actual: {$amenity->stock_actual})");
                                }
                            } catch (\Exception $e) {
                                \Log::error("Error descontando stock del amenity {$amenityId}: " . $e->getMessage());
                                Alert::error('Error de Stock', "Error al descontar stock del amenity '{$amenity->nombre}': {$e->getMessage()}");
                            }
                        } else {
                            \Log::error("No se pudo encontrar el amenity {$amenityId} para descontar stock");
                        }

                        $amenitiesCreados++;
                        \Log::info("Amenity {$amenityId} CREADO con cantidad {$cantidadDejada}");
                    }

                    $amenitiesGuardados++;
                }
            } catch (\Exception $e) {
                \Log::error("Error guardando amenity {$amenityId}: " . $e->getMessage());
                \Log::error("Datos del amenity: " . json_encode($amenityData));
            }
        }

        if ($amenitiesGuardados > 0) {
            $mensaje = "Se han procesado {$amenitiesGuardados} amenities: ";
            if ($amenitiesCreados > 0) {
                $mensaje .= "{$amenitiesCreados} creados";
            }
            if ($amenitiesActualizados > 0) {
                if ($amenitiesCreados > 0) $mensaje .= ", ";
                $mensaje .= "{$amenitiesActualizados} actualizados";
            }
            $mensaje .= " correctamente";

            // En lugar de Alert::success, pasamos el mensaje a la vista
            $mensajeAmenities = $mensaje;
        }
    }

    // Guardar observación
    $apartamentoLimpieza->observacion = $request->observacion;
    $apartamentoLimpieza->save();

    $id = $apartamentoLimpieza->id;
    Alert::success('Guardado con Éxito', 'Apartamento actualizado correctamente');

    $apartamentoId = $apartamentoLimpieza->apartamento_id;

    // Verificar que el apartamento existe
    $apartamento = Apartamento::find($apartamentoId);
    if (!$apartamento) {
        abort(404, 'Apartamento no encontrado');
    }

    $edificioId = $apartamento->edificio_id;

    // Verificar que el edificio existe
    if (!$edificioId) {
        abort(404, 'Edificio no encontrado para este apartamento');
    }

    $checklists = Checklist::with('items')->where('edificio_id', $edificioId)->get();
    $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();

    $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();
    $checklist_check = $item_check->whereNotNull('checklist_id')->filter(function ($item) {
        return $item->estado == 1;
    });
    $checklistsExistentes = $checklist_check->pluck('estado', 'checklist_id')->toArray();

    // Obtener amenities para esta limpieza
    $amenities = \App\Models\Amenity::activos()
        ->orderBy('categoria')
        ->orderBy('nombre')
        ->get()
        ->groupBy('categoria');

    // Obtener consumos existentes para esta limpieza
    $consumosExistentes = \App\Models\AmenityConsumo::where('limpieza_id', $apartamentoLimpieza->id)
        ->with('amenity')
        ->get()
        ->keyBy('amenity_id');

    // Calcular cantidades recomendadas para cada amenity
    $amenitiesConRecomendaciones = [];
    foreach ($amenities as $categoria => $amenitiesCategoria) {
        foreach ($amenitiesCategoria as $amenity) {
            $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, $apartamentoLimpieza->origenReserva, $apartamentoLimpieza->apartamento);
            $consumoExistente = $consumosExistentes->get($amenity->id);

            $amenitiesConRecomendaciones[$categoria][] = [
                'amenity' => $amenity,
                'cantidad_recomendada' => $cantidadRecomendada,
                'consumo_existente' => $consumoExistente,
                'stock_disponible' => $amenity->stock_actual
            ];
        }
    }

    return redirect()->route('gestion.edit', $apartamentoLimpieza)->with('mensajeAmenities', $mensajeAmenities ?? null);
}

/**
 * Actualizar limpieza de zona común
 */
public function updateZonaComun(Request $request, ApartamentoLimpieza $apartamentoLimpieza)
{
    // Verificar que sea una limpieza de zona común
    if ($apartamentoLimpieza->tipo_limpieza !== 'zona_comun') {
        abort(400, 'Esta función solo es válida para zonas comunes');
    }

    // Guardar observación
    $apartamentoLimpieza->observacion = $request->observacion;
    $apartamentoLimpieza->save();

    Alert::success('Guardado con Éxito', 'Zona común actualizada correctamente');

    return redirect()->route('gestion.edit', $apartamentoLimpieza);
}



    /**
     * Quick photo upload for the fast finalization flow.
     * Receives one photo at a time (compressed), stores it linked to the cleaning.
     */
    public function fotoRapida(Request $request, $id)
    {
        $limpieza = ApartamentoLimpieza::findOrFail($id);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $area = $request->get('area', 'general');
            $path = $file->store("limpiezas/{$id}", 'public');

            ApartamentoLimpiezaItem::create([
                'id_limpieza' => $limpieza->id,
                'photo_url' => $path,
                'photo_cat' => $area,
            ]);

            Log::info('[Limpieza] Foto rapida subida', [
                'limpieza_id' => $id,
                'area' => $area,
                'path' => $path,
                'user' => Auth::id(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function finalizar(Request $request, ApartamentoLimpieza $apartamentoLimpieza)
    {
        // Verificar que todos los checklists estén marcados
        $apartamentoId = $apartamentoLimpieza->apartamento_id;

        // Verificar que el apartamento existe
        $apartamento = Apartamento::find($apartamentoId);
        if (!$apartamento) {
            abort(404, 'Apartamento no encontrado');
        }

        $edificioId = $apartamento->edificio_id;

        // Verificar que el edificio existe
        if (!$edificioId) {
            abort(404, 'Edificio no encontrado para este apartamento');
        }

        $checklists = Checklist::where('edificio_id', $edificioId)->get();

        // Obtener los checklists marcados
        $checklistsMarcados = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)
            ->whereNotNull('checklist_id')
            ->where('estado', 1)
            ->pluck('checklist_id')
            ->toArray();

        // Verificar si faltan checklists por marcar
        $checklistsFaltantes = $checklists->whereNotIn('id', $checklistsMarcados);

        // Si faltan checklists, registrar pero NO bloquear
        if ($checklistsFaltantes->count() > 0) {
            $nombresFaltantes = $checklistsFaltantes->pluck('nombre')->implode(', ');
            $apartamentoLimpieza->consentimiento_finalizacion = true;
            $apartamentoLimpieza->motivo_consentimiento = $request->input('motivo_consentimiento', 'Finalizado con checks incompletos: ' . $nombresFaltantes);
            $apartamentoLimpieza->fecha_consentimiento = now();
            $apartamentoLimpieza->user_id_consentimiento = auth()->id();
            $apartamentoLimpieza->save();

            Log::info('[Limpieza] Finalizada con checks incompletos', [
                'limpieza_id' => $apartamentoLimpieza->id,
                'faltantes' => $nombresFaltantes,
            ]);
        }

        $hoy = Carbon::now();
        $apartamentoLimpieza->status_id = 3;
        $apartamentoLimpieza->fecha_fin = $hoy;
        $apartamentoLimpieza->save();

        // Notificar al huésped
        \App\Services\GuestCleaningNotificationService::notificar($apartamentoLimpieza);

        // Actualizar tarea asignada si existe
        if ($apartamentoLimpieza->tarea_asignada_id) {
            $tarea = TareaAsignada::find($apartamentoLimpieza->tarea_asignada_id);
            if ($tarea && $tarea->estado === 'en_progreso') {
                $tarea->update([
                    'estado' => 'completada',
                    'fecha_fin_real' => $hoy
                ]);

                Log::info('Tarea finalizada desde limpieza', [
                    'tarea_id' => $tarea->id,
                    'limpieza_id' => $apartamentoLimpieza->id,
                    'fecha_fin' => $hoy
                ]);
            }
        }

        $reserva = Reserva::find($apartamentoLimpieza->reserva_id);
        if ($reserva != null) {
            $reserva->fecha_limpieza = $hoy;
            $reserva->save();
        }

        // DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
        $this->descontarAmenitiesLimpieza($apartamentoLimpieza);

        // Crear alerta si hay observaciones al finalizar la limpieza
        if (!empty($apartamentoLimpieza->observacion)) {
            $apartamentoNombre = $apartamentoLimpieza->apartamento->nombre ?? 'Apartamento';
            AlertService::createCleaningObservationAlert(
                $apartamentoLimpieza->id,
                $apartamentoNombre,
                $apartamentoLimpieza->observacion
            );
        }

        Alert::success('Finalizado con Éxito', 'Apartamento Finalizado correctamente');

        // Limpiadoras vuelven a su dashboard, admins a gestión
        if (auth()->user()->role === 'LIMPIEZA') {
            return redirect('/limpiadora/dashboard');
        }
        return redirect()->route('gestion.index');
    }

    /**
     * Registrar descuento de artículo roto y reposición automática 1:1 del mismo artículo si hay stock.
     */
    public function registrarDescuentoArticulo(
        Request $request,
        ApartamentoLimpieza $apartamentoLimpieza,
        ArticuloStockService $articuloStockService
    ) {
        $request->validate([
            'articulo_id' => 'required|exists:articulos,id',
            'motivo' => 'required|string|in:roto,danado,perdido,desgastado,otro',
            'observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $resultados = $articuloStockService->salidaAutoReposicionMismoArticulo(
                articuloId: (int) $request->input('articulo_id'),
                apartamentoLimpiezaId: $apartamentoLimpieza->id,
                motivoRoto: (string) $request->input('motivo'),
                observaciones: $request->input('observaciones')
            );

            $reposHecha = $resultados['reposicion'] !== null;

            return response()->json([
                'success' => true,
                'message' => $reposHecha
                    ? 'Artículo roto descontado y reposición automática realizada'
                    : 'Artículo roto descontado (sin stock para reposición)',
                'reposicion_realizada' => $reposHecha,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Error registrarDescuentoArticulo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Finalizar limpieza de zona común
     */
    public function finalizarZonaComun(ApartamentoLimpieza $apartamentoLimpieza)
    {
        // Verificar que sea una limpieza de zona común
        if ($apartamentoLimpieza->tipo_limpieza !== 'zona_comun') {
            abort(400, 'Esta función solo es válida para zonas comunes');
        }

        $hoy = Carbon::now();
        $apartamentoLimpieza->status_id = 3; // Finalizado
        $apartamentoLimpieza->fecha_fin = $hoy;
        $apartamentoLimpieza->save();

        // Notificar al huésped
        \App\Services\GuestCleaningNotificationService::notificar($apartamentoLimpieza);

        // Actualizar tarea asignada si existe
        if ($apartamentoLimpieza->tarea_asignada_id) {
            $tarea = TareaAsignada::find($apartamentoLimpieza->tarea_asignada_id);
            if ($tarea && $tarea->estado === 'en_progreso') {
                $tarea->update([
                    'estado' => 'completada',
                    'fecha_fin_real' => $hoy
                ]);

                Log::info('Tarea finalizada desde limpieza de zona común', [
                    'tarea_id' => $tarea->id,
                    'limpieza_id' => $apartamentoLimpieza->id,
                    'fecha_fin' => $hoy
                ]);
            }
        }

        // DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
        $this->descontarAmenitiesLimpieza($apartamentoLimpieza);

        // Crear alerta si hay observaciones al finalizar la limpieza
        if (!empty($apartamentoLimpieza->observacion)) {
            $zonaComunNombre = $apartamentoLimpieza->zonaComun->nombre ?? 'Zona Común';
            AlertService::createCleaningObservationAlert(
                $apartamentoLimpieza->id,
                $zonaComunNombre,
                $apartamentoLimpieza->observacion
            );
        }

        Alert::success('Finalizado con Éxito', 'Zona Común finalizada correctamente');

        if (auth()->user()->role === 'LIMPIEZA') {
            return redirect('/limpiadora/dashboard');
        }
        return redirect()->route('gestion.index');
    }

    public function limpiezaFondo(Request $request) {
        $apartamentos = LimpiezaFondo::all();
        return view('admin.limpieza.index', compact('apartamentos'));

    }

    public function limpiezaFondoDestroy($id) {
        $limpieza = LimpiezaFondo::find($id);
        $limpieza->delete();
        return redirect(route('admin.limpiezaFondo.index'));

    }

    public function limpiezaCreate(Request $request) {
        $apartamentos = Apartamento::all();
        return view('admin.limpieza.create', compact('apartamentos'));

    }
    public function limpiezaFondoEdit($id) {
        $limpieza = LimpiezaFondo::find($id);
        $apartamentos = Apartamento::all();

        return view('admin.limpieza.edit', compact('apartamentos', 'limpieza'));

    }

    public function limpiezaFondoStore(Request $request) {
        $rules = [
            'fecha' => 'required|date',
            'apartamento_id' => 'required'
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $limpiezaAFondo = LimpiezaFondo::create([
            'apartamento_id' => $request->apartamento_id,
            'fecha' => $request->fecha
        ]);
        Alert::success('Fizalizado con Exito', 'Apartamento Fizalizado correctamente');

        return redirect()->route('admin.limpiezaFondo.index');
    }

    /**
     * Resuelve una TareaAsignada para la limpieza cuando no tiene tarea_asignada_id.
     * Busca una tarea del usuario actual para el mismo apartamento (turno de hoy, activo o con tarea pendiente/en_progreso).
     */
    protected function resolverTareaParaLimpieza(ApartamentoLimpieza $apartamentoLimpieza): ?TareaAsignada
    {
        $userId = Auth::id();
        $apartamentoId = $apartamentoLimpieza->apartamento_id;
        $hoy = Carbon::today();

        $estadosTareaValidos = function ($query) {
            $query->whereIn('estado', ['pendiente', 'en_progreso'])->orWhereNull('estado');
        };

        // Turno de hoy del usuario: programado, en_progreso (cuando ya activó el turno) o activo
        $turnoHoy = TurnoTrabajo::where('user_id', $userId)
            ->where('fecha', $hoy)
            ->whereIn('estado', ['activo', 'programado', 'en_progreso'])
            ->first();

        if ($turnoHoy) {
            $tarea = TareaAsignada::where('turno_id', $turnoHoy->id)
                ->where('apartamento_id', $apartamentoId)
                ->where($estadosTareaValidos)
                ->first();
            if ($tarea) {
                return $tarea;
            }
        }

        // Cualquier turno de hoy del usuario con tarea para este apartamento (por si el estado del turno difiere)
        $tarea = TareaAsignada::whereHas('turno', function ($q) use ($userId, $hoy) {
            $q->where('user_id', $userId)->where('fecha', $hoy);
        })
            ->where('apartamento_id', $apartamentoId)
            ->where($estadosTareaValidos)
            ->first();

        if (!$tarea) {
            Log::warning('resolverTareaParaLimpieza: no se encontró tarea', [
                'user_id' => $userId,
                'apartamento_id' => $apartamentoId,
                'limpieza_id' => $apartamentoLimpieza->id,
                'hoy' => $hoy->toDateString(),
                'turno_encontrado' => $turnoHoy ? $turnoHoy->id : null,
            ]);
        }

        return $tarea;
    }

    /**
     * Update checkbox state via AJAX
     */
    public function updateCheckbox(Request $request)
    {
        try {
            $type = $request->input('type');
            $id = $request->input('id');
            $checked = $request->input('checked');
            $limpiezaId = $request->input('limpieza_id');
            $tareaId = $request->input('tarea_id'); // Aceptar tarea_id directamente

            Log::info('updateCheckbox llamado', [
                'type' => $type,
                'id' => $id,
                'checked' => $checked,
                'limpieza_id' => $limpiezaId,
                'tarea_id' => $tareaId
            ]);

            // Intentar obtener la tarea de diferentes formas
            if ($tareaId) {
                // Si se envía tarea_id directamente, usarlo
                $tarea = TareaAsignada::find($tareaId);
                if (!$tarea) {
                    // Puede venir un tarea_id "stale" (la limpieza apunta a una tarea inexistente).
                    // En ese caso, intentamos recuperar la tarea desde la limpieza / turno del usuario.
                    Log::warning('Tarea no encontrada por ID (posible referencia obsoleta), intentando resolver por limpieza', [
                        'tarea_id' => $tareaId,
                        'limpieza_id' => $limpiezaId,
                    ]);

                    $tareaId = null;
                }
                if ($tarea) {
                    $tareaId = $tarea->id;
                }
            } elseif ($limpiezaId) {
                // Si se envía limpieza_id, buscar la tarea a través de la limpieza
                $apartamentoLimpieza = ApartamentoLimpieza::find($limpiezaId);
                if (!$apartamentoLimpieza) {
                    Log::error('Limpieza no encontrada', ['limpieza_id' => $limpiezaId]);
                    return response()->json(['success' => false, 'message' => 'Limpieza no encontrada'], 404);
                }

                $tareaId = $apartamentoLimpieza->tarea_asignada_id;
                if (!$tareaId) {
                    // Limpieza sin tarea asociada (ej. entrada por gestion-create sin turno activo): intentar resolver tarea del usuario para este apartamento
                    $tareaAsignada = $this->resolverTareaParaLimpieza($apartamentoLimpieza);
                    if ($tareaAsignada) {
                        $apartamentoLimpieza->tarea_asignada_id = $tareaAsignada->id;
                        $apartamentoLimpieza->save();
                        $tareaId = $tareaAsignada->id;
                        Log::info('Tarea asociada a limpieza (resuelta por usuario y apartamento)', [
                            'limpieza_id' => $limpiezaId,
                            'tarea_id' => $tareaId,
                        ]);
                    } else {
                        Log::error('Tarea no encontrada en limpieza', [
                            'limpieza_id' => $limpiezaId,
                            'tarea_asignada_id' => $apartamentoLimpieza->tarea_asignada_id
                        ]);
                        return response()->json(['success' => false, 'message' => 'Tarea no encontrada'], 404);
                    }
                }
            } else {
                Log::error('No se proporcionó tarea_id ni limpieza_id');
                return response()->json(['success' => false, 'message' => 'Se requiere tarea_id o limpieza_id'], 400);
            }

            // Si tras intentar por tarea_id seguimos sin tarea, intentar resolver por limpieza_id si está disponible
            if (!$tareaId && $limpiezaId) {
                $apartamentoLimpieza = ApartamentoLimpieza::find($limpiezaId);
                if ($apartamentoLimpieza) {
                    $tareaAsignada = $this->resolverTareaParaLimpieza($apartamentoLimpieza);
                    if ($tareaAsignada) {
                        $apartamentoLimpieza->tarea_asignada_id = $tareaAsignada->id;
                        $apartamentoLimpieza->save();
                        $tareaId = $tareaAsignada->id;
                        Log::info('Tarea resuelta por limpieza tras tarea_id inexistente', [
                            'limpieza_id' => $limpiezaId,
                            'tarea_id' => $tareaId,
                        ]);
                    }
                }
            }

            // Obtener la tarea para verificar permisos y actualizar estado
            $tarea = TareaAsignada::find($tareaId);
            if (!$tarea) {
                Log::error('Tarea no encontrada', ['tarea_id' => $tareaId]);
                return response()->json(['success' => false, 'message' => 'Tarea no encontrada'], 404);
            }

            // Verificar que la tarea pertenece al usuario autenticado
            if ($tarea->turno && $tarea->turno->user_id !== Auth::id()) {
                Log::error('Usuario no autorizado para esta tarea', [
                    'tarea_id' => $tareaId,
                    'user_id' => Auth::id(),
                    'tarea_user_id' => $tarea->turno->user_id
                ]);
                return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
            }

            if ($type === 'item') {
                if ($checked == 1) {
                    // Insertar o actualizar en tarea_checklist_completados
                    DB::table('tarea_checklist_completados')->updateOrInsert(
                        [
                            'tarea_asignada_id' => $tareaId,
                            'item_checklist_id' => $id
                        ],
                        [
                            'completado_por' => Auth::id(),
                            'fecha_completado' => now(),
                            'estado' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                    Log::info('Item marcado como completado', ['tarea_id' => $tareaId, 'item_id' => $id]);

                    // Actualizar estado de la tarea a "en_progreso" si está pendiente
                    if ($tarea->estado === 'pendiente' || $tarea->estado === null) {
                        $tarea->estado = 'en_progreso';
                        $tarea->fecha_inicio_real = $tarea->fecha_inicio_real ?? now();
                        $tarea->save();
                        Log::info('Tarea actualizada a "en_progreso"', ['tarea_id' => $tareaId]);
                    }
                } else {
                    // Eliminar de tarea_checklist_completados
                    DB::table('tarea_checklist_completados')
                        ->where('tarea_asignada_id', $tareaId)
                        ->where('item_checklist_id', $id)
                        ->delete();
                    Log::info('Item desmarcado', ['tarea_id' => $tareaId, 'item_id' => $id]);
                }
            } else if ($type === 'checklist') {
                if ($checked == 1) {
                    // Marcar checklist como completado
                    DB::table('tarea_checklist_completados')->updateOrInsert(
                        [
                            'tarea_asignada_id' => $tareaId,
                            'checklist_id' => $id
                        ],
                        [
                            'completado_por' => Auth::id(),
                            'fecha_completado' => now(),
                            'estado' => 1,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]
                    );
                    Log::info('Checklist marcado como completado', ['tarea_id' => $tareaId, 'checklist_id' => $id]);
                } else {
                    // Desmarcar checklist
                    DB::table('tarea_checklist_completados')
                        ->where('tarea_asignada_id', $tareaId)
                        ->where('checklist_id', $id)
                        ->delete();
                    Log::info('Checklist desmarcado', ['tarea_id' => $tareaId, 'checklist_id' => $id]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error en updateCheckbox: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get checklist status for AJAX requests
     */
    public function checklistStatus(ApartamentoLimpieza $apartamentoLimpieza)
    {
        $apartamentoId = $apartamentoLimpieza->apartamento_id;

        // Verificar que el apartamento existe
        $apartamento = Apartamento::find($apartamentoId);
        if (!$apartamento) {
            return response()->json(['error' => 'Apartamento no encontrado'], 404);
        }

        $edificioId = $apartamento->edificio_id;

        // Verificar que el edificio existe
        if (!$edificioId) {
            return response()->json(['error' => 'Edificio no encontrado para este apartamento'], 404);
        }

        $checklists = Checklist::where('edificio_id', $edificioId)->get();

        $checklistsMarcados = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)
            ->whereNotNull('checklist_id')
            ->where('estado', 1)
            ->pluck('checklist_id')
            ->toArray();

        $checklistsFaltantes = $checklists->whereNotIn('id', $checklistsMarcados);

        return response()->json([
            'total' => $checklists->count(),
            'completados' => count($checklistsMarcados),
            'faltantes' => $checklistsFaltantes->pluck('nombre')->toArray(),
            'puedeFinalizar' => $checklistsFaltantes->count() === 0
        ]);
    }

    /**
     * Editar limpieza de zona común
     */
    public function editZonaComun($id)
    {
        $apartamentoLimpieza = ApartamentoLimpieza::with(['zonaComun', 'empleada', 'status'])
            ->where('id', $id)
            ->where('tipo_limpieza', 'zona_comun')
            ->firstOrFail();

        $zonaComun = $apartamentoLimpieza->zonaComun;
        if (!$zonaComun) {
            abort(404, 'Zona común no encontrada');
        }

        // Obtener checklists para zonas comunes
        $checklists = \App\Models\ChecklistZonaComun::activos()->ordenados()->get();

        // Obtener items existentes para esta limpieza
        $item_check = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
        $itemsExistentes = $item_check->pluck('estado', 'item_id')->toArray();

        // Obtener checklists marcados
        $checklist_check = $item_check->whereNotNull('checklist_zona_comun_id')->filter(function ($item) {
            return $item->estado == 1;
        });
        $checklistsExistentes = $checklist_check->pluck('estado', 'checklist_zona_comun_id')->toArray();

        return view('gestion.edit-zona-comun', compact(
            'apartamentoLimpieza',
            'zonaComun',
            'id',
            'checklists',
            'itemsExistentes',
            'checklistsExistentes'
        ));
    }

    /**
     * Crear limpieza para zona común
     */
    public function createZonaComun($id)
    {
        $zonaComun = \App\Models\ZonaComun::findOrFail($id);

        // Verificar si ya existe una limpieza activa para esta zona
        $limpiezaExistente = ApartamentoLimpieza::where('zona_comun_id', $id)
            ->where('tipo_limpieza', 'zona_comun')
            ->whereNull('fecha_fin')
            ->first();

        if ($limpiezaExistente) {
            Alert::warning('Atención', 'Ya existe una limpieza activa para esta zona común.');
            return redirect()->route('gestion.index');
        }

        // Crear nueva limpieza para zona común
        $usuarioActual = Auth::user();
        if ($usuarioActual->inactive) {
            Alert::error('Error', 'No se puede crear la limpieza: el usuario está inactivo');
            return redirect()->route('gestion.index');
        }

        $apartamentoLimpieza = ApartamentoLimpieza::create([
            'zona_comun_id' => $id,
            'tipo_limpieza' => 'zona_comun',
            'fecha_comienzo' => Carbon::now(),
            'status_id' => 2, // En proceso
            'empleada_id' => $usuarioActual->id,
            'user_id' => $usuarioActual->id
        ]);

        Alert::success('Éxito', 'Limpieza de zona común iniciada correctamente.');
        return redirect()->route('gestion.edit', $apartamentoLimpieza->id);
    }

    /**
     * Get checklist status for zona común AJAX requests
     */
    public function checklistStatusZonaComun(ApartamentoLimpieza $apartamentoLimpieza)
    {
        if ($apartamentoLimpieza->tipo_limpieza !== 'zona_comun') {
            return response()->json(['error' => 'No es una limpieza de zona común'], 400);
        }

        $checklists = \App\Models\ChecklistZonaComun::activos()->ordenados()->get();

        $checklistsMarcados = ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)
            ->whereNotNull('checklist_zona_comun_id')
            ->where('estado', 1)
            ->pluck('checklist_zona_comun_id')
            ->toArray();

        $checklistsFaltantes = $checklists->whereNotIn('id', $checklistsMarcados);

        return response()->json([
            'total' => $checklists->count(),
            'completados' => count($checklistsMarcados),
            'faltantes' => $checklistsFaltantes->pluck('nombre')->toArray(),
            'puedeFinalizar' => $checklistsFaltantes->count() === 0
        ]);
    }

    /**
     * Calcular cantidad recomendada para un amenity según las reglas de consumo
     */
    private function calcularCantidadRecomendadaAmenity($amenity, $reserva, $apartamento)
    {
        return AmenityConsumptionService::calculateRecommendedQuantity($amenity, $reserva, $apartamento);
    }

    /**
     * Mostrar información de una reserva
     */
    public function mostrarInfoReserva($id)
    {
        $reserva = Reserva::with(['apartamento', 'cliente'])->findOrFail($id);
        return view('gestion.reserva-info', compact('reserva'));
    }

    /**
     * Ver limpieza completada (solo lectura)
     */
    public function verLimpiezaCompletada($id)
    {
        $apartamentoLimpieza = ApartamentoLimpieza::with([
            'apartamento.edificio',
            'zonaComun',
            'empleada',
            'estado',
            'reserva',
            'fotos' => function($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Combinar fotos del sistema antiguo (photos) + fotos rapidas (apartamento_limpieza_items)
        $fotosAntiguas = $apartamentoLimpieza->fotos ?? collect();
        $fotosRapidas = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)
            ->whereNotNull('photo_url')
            ->where('photo_url', '!=', '')
            ->get()
            ->map(function($item) {
                $item->url = 'storage/' . $item->photo_url;
                $item->descripcion = ucfirst($item->photo_cat ?? 'Foto');
                $item->photo_cat = $item->photo_cat ?? 'general';
                return $item;
            });
        $todasLasFotos = $fotosAntiguas->concat($fotosRapidas);


        // Obtener checklists con sus items si existen
        $checklists = [];
        if ($apartamentoLimpieza->apartamento && $apartamentoLimpieza->apartamento->edificio_id) {
            $checklists = \App\Models\Checklist::with('items')->where('edificio_id', $apartamentoLimpieza->apartamento->edificio_id)->get();
        }

        // Obtener items existentes de la limpieza
        $itemsExistentes = [];
        if ($apartamentoLimpieza->id) {
            $itemsExistentes = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
        }

        return view('gestion.ver-limpieza', compact(
            'apartamentoLimpieza',
            'checklists',
            'itemsExistentes',
            'todasLasFotos'
        ));
    }

    /**
     * DESCUENTO AUTOMÁTICO DE AMENITIES DE LIMPIEZA
     */
    private function descontarAmenitiesLimpieza(ApartamentoLimpieza $apartamentoLimpieza)
    {
        try {
            \Log::info('Iniciando descuento automático de amenities para limpieza ID: ' . $apartamentoLimpieza->id);

            // Obtener TODOS los amenities activos (no solo los de limpieza)
            $amenitiesLimpieza = \App\Models\Amenity::where('activo', true)
                ->get();

            \Log::info('Amenities activos encontrados: ' . $amenitiesLimpieza->count());

            $totalGasto = 0;
            $amenitiesUsados = [];

            foreach ($amenitiesLimpieza as $amenity) {
                \Log::info('Procesando amenity: ' . $amenity->nombre . ' (Categoría: ' . $amenity->categoria . ', Stock: ' . $amenity->stock_actual . ')');

                // Calcular cantidad recomendada para esta limpieza
                $cantidadRecomendada = $this->calcularCantidadRecomendadaAmenity($amenity, $apartamentoLimpieza->reserva, $apartamentoLimpieza->apartamento);

                \Log::info('Cantidad recomendada calculada: ' . $cantidadRecomendada);

                if ($cantidadRecomendada > 0) {
                    \Log::info('Cantidad > 0, verificando stock...');

                    try {
                        // Usar el método estándar para descontar stock
                        \Log::info('Stock suficiente, procediendo con descuento...');

                        $resultadoDescuento = $amenity->descontarStock($cantidadRecomendada);

                        // Validar que el resultado sea un array
                        if (!is_array($resultadoDescuento)) {
                            \Log::error('Error: descontarStock no devolvió un array. Tipo: ' . gettype($resultadoDescuento) . ', Valor: ' . var_export($resultadoDescuento, true));
                            throw new \Exception("Error al descontar stock: resultado inválido del método descontarStock()");
                        }

                        // Validar que tenga las claves necesarias
                        if (!isset($resultadoDescuento['stock_anterior']) || !isset($resultadoDescuento['stock_actual'])) {
                            \Log::error('Error: descontarStock no devolvió las claves esperadas. Claves: ' . implode(', ', array_keys($resultadoDescuento)));
                            throw new \Exception("Error al descontar stock: resultado incompleto del método descontarStock()");
                        }

                        \Log::info('Stock actualizado: ' . $resultadoDescuento['stock_anterior'] . ' -> ' . $resultadoDescuento['stock_actual']);

                        // Calcular costo
                        $costoTotal = $cantidadRecomendada * $amenity->precio_compra;
                        $totalGasto += $costoTotal;

                        \Log::info('Costo calculado: €' . $costoTotal);

                        // Registrar el consumo con datos reales
                        \Log::info('Creando registro de consumo...');
                        \App\Models\AmenityConsumo::create([
                            'amenity_id' => $amenity->id,
                            'reserva_id' => $apartamentoLimpieza->reserva_id,
                            'apartamento_id' => $apartamentoLimpieza->apartamento_id,
                            'limpieza_id' => $apartamentoLimpieza->id,
                            'user_id' => auth()->id(),
                            'tipo_consumo' => 'limpieza',
                            'cantidad_consumida' => $cantidadRecomendada,
                            'cantidad_anterior' => $resultadoDescuento['stock_anterior'],
                            'cantidad_actual' => $resultadoDescuento['stock_actual'],
                            'costo_unitario' => $amenity->precio_compra,
                            'costo_total' => $costoTotal,
                            'observaciones' => 'Descuento automático al finalizar limpieza',
                            'fecha_consumo' => now()
                        ]);

                        \Log::info('Consumo registrado exitosamente');

                        $amenitiesUsados[] = [
                            'nombre' => $amenity->nombre,
                            'cantidad' => $cantidadRecomendada,
                            'unidad' => $amenity->unidad_medida,
                            'costo' => $costoTotal
                        ];

                        // Verificar si el stock está bajo después del descuento
                        if ($amenity->verificarStockBajo()) {
                            \Alert::warning('Stock Bajo', "El amenity '{$amenity->nombre}' tiene stock bajo (actual: {$amenity->stock_actual} {$amenity->unidad_medida})");
                            // CRM notification
                            try {
                                \App\Services\NotificationService::notifyLowStock($amenity->id, $amenity->nombre, $amenity->stock_actual, $amenity->stock_minimo);
                            } catch (\Exception $e) {
                                \Log::error('Error notificación stock bajo: ' . $e->getMessage());
                            }
                            // WhatsApp alert
                            try {
                                \App\Services\AlertaEquipoService::alertar(
                                    'STOCK BAJO - AMENITY',
                                    "Amenity: " . $amenity->nombre . "\n"
                                    . "Stock actual: " . $amenity->stock_actual . "\n"
                                    . "Stock mínimo: " . $amenity->stock_minimo,
                                    'stock_bajo'
                                );
                            } catch (\Exception $e) {
                                \Log::error('Error WhatsApp stock bajo: ' . $e->getMessage());
                            }
                        }

                    } catch (\Exception $e) {
                        // Stock insuficiente
                        \Log::warning('Stock insuficiente: ' . $e->getMessage());
                        \Alert::error('Stock Insuficiente', "No hay suficiente stock de '{$amenity->nombre}' para esta limpieza. {$e->getMessage()}");
                    }
                }
            }

            // Mostrar resumen de amenities utilizados
            if (!empty($amenitiesUsados)) {
                $mensaje = "Amenities utilizados automáticamente:\n";
                foreach ($amenitiesUsados as $amenity) {
                    $mensaje .= "• {$amenity['nombre']}: {$amenity['cantidad']} {$amenity['unidad']} (€{$amenity['costo']})\n";
                }
                $mensaje .= "\nTotal gasto en amenities: €{$totalGasto}";

                \Alert::info('Amenities Aplicados', $mensaje);
            }

        } catch (\Exception $e) {
            \Log::error('Error al descontar amenities de limpieza: ' . $e->getMessage());
            \Alert::error('Error', 'Error al procesar amenities de limpieza: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estadísticas del dashboard de limpieza
     */
    private function getDashboardStats()
    {
        $user = Auth::user();
        $hoy = Carbon::today();

        try {
            // Obtener apartamentos que SALEN hoy (necesitan limpieza) - misma lógica que /gestion
            $apartamentosPendientesHoy = \DB::table('reservas')
                ->whereNull('fecha_limpieza')
                ->where('estado_id', '!=', 4)
                ->whereDate('fecha_salida', $hoy)
                ->pluck('apartamento_id')
                ->toArray();

            // Obtener limpiezas de fondo programadas para hoy
            $limpiezasFondoHoy = \DB::table('limpieza_fondo')
                ->whereDate('fecha', $hoy)
                ->pluck('apartamento_id')
                ->toArray();

            // Combinar todos los apartamentos que necesitan limpieza hoy
            $apartamentosNecesitanLimpieza = array_merge($apartamentosPendientesHoy, $limpiezasFondoHoy);
            $apartamentosNecesitanLimpieza = array_unique($apartamentosNecesitanLimpieza);

            // Obtener limpiezas ya asignadas a esta empleada para hoy
            $limpiezasAsignadasHoy = \DB::table('apartamento_limpieza')
                ->where('empleada_id', $user->id)
                ->whereDate('fecha_comienzo', $hoy)
                ->pluck('apartamento_id')
                ->toArray();

            // Apartamentos pendientes de limpieza (necesitan limpieza pero no están asignados)
            $apartamentosPendientes = array_diff($apartamentosNecesitanLimpieza, $limpiezasAsignadasHoy);

            // Estadísticas del día
            $limpiezasHoy = count($apartamentosNecesitanLimpieza); // Total de apartamentos que necesitan limpieza
            $limpiezasAsignadas = count($limpiezasAsignadasHoy); // Total de limpiezas asignadas a esta empleada

            // Obtener limpiezas completadas hoy por esta empleada
            $limpiezasCompletadasHoy = \DB::table('apartamento_limpieza')
                ->where('empleada_id', $user->id)
                ->whereDate('fecha_comienzo', $hoy)
                ->where('status_id', 2) // Completada
                ->count();

            // Obtener limpiezas en proceso hoy por esta empleada
            $limpiezasPendientesHoy = \DB::table('apartamento_limpieza')
                ->where('empleada_id', $user->id)
                ->whereDate('fecha_comienzo', $hoy)
                ->where('status_id', 1) // En proceso
                ->count();

            // Obtener incidencias pendientes del usuario
            $incidenciasPendientes = \DB::table('incidencias')
                ->where('empleada_id', $user->id)
                ->where('estado', 'pendiente')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            // Obtener estadísticas de la semana
            $inicioSemana = $hoy->copy()->startOfWeek();
            $finSemana = $hoy->copy()->endOfWeek();

            // Limpiezas asignadas a esta empleada en la semana
            $limpiezasSemana = \DB::table('apartamento_limpieza')
                ->where('empleada_id', $user->id)
                ->whereBetween('fecha_comienzo', [$inicioSemana, $finSemana])
                ->count();

            $limpiezasCompletadasSemana = \DB::table('apartamento_limpieza')
                ->where('empleada_id', $user->id)
                ->whereBetween('fecha_comienzo', [$inicioSemana, $finSemana])
                ->where('status_id', 2)
                ->count();

            // Calcular porcentaje de completado de la semana
            $porcentajeSemana = $limpiezasSemana > 0 ? round(($limpiezasCompletadasSemana / $limpiezasSemana) * 100) : 0;

            // Obtener estado del fichaje actual
            $fichajeActual = Fichaje::where('user_id', $user->id)
                ->whereDate('hora_entrada', $hoy)
                ->whereNull('hora_salida')
                ->first();

            // Obtener estadísticas de calidad de limpieza (si existen análisis)
            $analisisRecientes = [];
            try {
                $analisisRecientes = \DB::table('photo_analyses')
                    ->where('empleada_id', $user->id)
                    ->whereDate('fecha_analisis', '>=', $hoy->copy()->subDays(7))
                    ->select('calidad_general', \DB::raw('count(*) as total'))
                    ->groupBy('calidad_general')
                    ->get()
                    ->pluck('total', 'calidad_general')
                    ->toArray();
            } catch (\Exception $e) {
                // Si hay error, usar array vacío
                $analisisRecientes = [];
            }

            return [
                'limpiezasHoy' => $limpiezasHoy,
                'limpiezasAsignadas' => $limpiezasAsignadas,
                'limpiezasCompletadasHoy' => $limpiezasCompletadasHoy,
                'limpiezasPendientesHoy' => $limpiezasPendientesHoy,
                'apartamentosPendientes' => count($apartamentosPendientes),
                'incidenciasPendientes' => $incidenciasPendientes,
                'limpiezasSemana' => $limpiezasSemana,
                'limpiezasCompletadasSemana' => $limpiezasCompletadasSemana,
                'porcentajeSemana' => $porcentajeSemana,
                'fichajeActual' => $fichajeActual,
                'analisisRecientes' => $analisisRecientes,
                'hoy' => $hoy->format('d/m/Y'),
                'diaSemana' => $hoy->locale('es')->dayName
            ];

        } catch (\Exception $e) {
            \Log::error('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());

            return [
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
                'analisisRecientes' => [],
                'hoy' => $hoy->format('d/m/Y'),
                'diaSemana' => $hoy->locale('es')->dayName,
                'error' => 'Error al cargar datos: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener estadísticas del dashboard via AJAX
     */
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
            ->whereBetween('hora_entrada', [$inicioMes->startOfDay(), $hoy->endOfDay()])
            ->whereNotNull('hora_salida')
            ->get()
            ->sum(function($fichaje) {
                if ($fichaje->hora_entrada && $fichaje->hora_salida) {
                    $inicio = Carbon::parse($fichaje->hora_entrada);
                    $fin = Carbon::parse($fichaje->hora_salida);
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
