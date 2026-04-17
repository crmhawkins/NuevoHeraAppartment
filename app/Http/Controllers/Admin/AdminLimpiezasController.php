<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ApartamentoLimpieza;
use App\Models\PhotoAnalysis;
use App\Models\User;
use App\Models\Apartamento;
use App\Models\ApartamentoLimpiezaItem;
use App\Models\TareaChecklistCompletado;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminLimpiezasController extends Controller
{
    public function index(Request $request)
    {
        // Obtener datos para filtros - SOLO USUARIOS ACTIVOS
        $empleadas = User::whereIn('role', ['USER', 'LIMPIEZA', 'ADMIN'])
            ->where(function($query) {
                $query->where('inactive', '=', 0)
                      ->orWhereNull('inactive');
            })
            ->orderBy('name')
            ->get();
        
        $apartamentos = Apartamento::orderBy('nombre')->get();
        $zonasComunes = \App\Models\ZonaComun::activas()->ordenadas()->get();
        
        $estados = \App\Models\ApartamentoLimpiezaEstado::orderBy('nombre')->get();

        // Construir query base
        $query = ApartamentoLimpieza::with([
            'apartamento',
            'zonaComun',
            'user',
            'empleada',  // Agregar relación empleada
            'estado',
            'itemsMarcados.item',
            'fotos',
            'analisis'
        ])
        ->withCount(['analisis'])
        ->withCount([
            'itemsMarcados as total_fotos' => function($q) { $q->whereNotNull('photo_url'); }
        ])
        ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_comienzo', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_comienzo', '<=', $request->fecha_hasta);
        }

        if ($request->filled('empleada')) {
            $query->where(function($q) use ($request) {
                $q->where('user_id', $request->empleada)
                  ->orWhere('empleada_id', $request->empleada);
            });
        }

        if ($request->filled('apartamento')) {
            if ($request->apartamento === 'zona_comun') {
                $query->whereNotNull('zona_comun_id');
            } else {
                $query->where('apartamento_id', $request->apartamento);
            }
        }

        if ($request->filled('estado')) {
            $query->where('status_id', $request->estado);
        }

        if ($request->filled('con_fotos')) {
            if ($request->con_fotos === 'si') {
                $query->whereHas('fotos');
            } else {
                $query->whereDoesntHave('fotos');
            }
        }

        if ($request->filled('con_analisis')) {
            if ($request->con_analisis === 'si') {
                $query->whereHas('analisis');
            } else {
                $query->whereDoesntHave('analisis');
            }
        }

        // Filtro para mostrar solo limpiezas de hoy
        if ($request->filled('fecha_hoy') && $request->fecha_hoy === 'si') {
            $query->whereDate('created_at', today());
        }

        // Filtro para consentimiento
        if ($request->filled('con_consentimiento')) {
            if ($request->con_consentimiento === 'si') {
                $query->where('consentimiento_finalizacion', true);
            } elseif ($request->con_consentimiento === 'no') {
                $query->where('consentimiento_finalizacion', false);
            }
        }

        // Calculate summary statistics from the FULL query before pagination
        $summaryQuery = clone $query;
        $limpiezasHoy = (clone $summaryQuery)->where('created_at', '>=', now()->startOfDay())->count();
        $zonasHoy = (clone $summaryQuery)->where('tipo_limpieza', 'zona_comun')->where('created_at', '>=', now()->startOfDay())->count();
        $apartamentosHoy = (clone $summaryQuery)->where('tipo_limpieza', '!=', 'zona_comun')->where('created_at', '>=', now()->startOfDay())->count();
        $completadasHoy = (clone $summaryQuery)->where('created_at', '>=', now()->startOfDay())->where('status_id', 3)->count();
        $conConsentimiento = (clone $summaryQuery)->where('consentimiento_finalizacion', true)->count();

        // Obtener limpiezas paginadas
        $limpiezas = $query->paginate(20);

        // Preload checklist counts for all limpiezas in this page to avoid N+1
        $tareaIds = $limpiezas->pluck('tarea_asignada_id')->filter()->unique();
        $checklistCounts = TareaChecklistCompletado::whereIn('tarea_asignada_id', $tareaIds)
            ->whereNotNull('item_checklist_id')
            ->selectRaw('tarea_asignada_id, COUNT(*) as total, SUM(CASE WHEN estado = 1 THEN 1 ELSE 0 END) as completados')
            ->groupBy('tarea_asignada_id')
            ->get()
            ->keyBy('tarea_asignada_id');

        // Preparar filtros para la vista
        $filtros = [
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'empleada' => $request->empleada,
            'apartamento' => $request->apartamento,
            'estado' => $request->estado,
            'con_fotos' => $request->con_fotos,
            'con_analisis' => $request->con_analisis,
            'fecha_hoy' => $request->fecha_hoy,
            'con_consentimiento' => $request->con_consentimiento
        ];

        return view('admin.limpiezas.index', compact(
            'limpiezas',
            'empleadas',
            'apartamentos',
            'zonasComunes',
            'estados',
            'filtros',
            'checklistCounts',
            'limpiezasHoy',
            'zonasHoy',
            'apartamentosHoy',
            'completadasHoy',
            'conConsentimiento'
        ));
    }

    public function show($id)
    {
        $limpieza = ApartamentoLimpieza::with([
            'apartamento', 
            'empleada', 
            'estado',
            'reserva',
            'itemsMarcados.item',
            'fotos',
            'analisis',
            'amenitiesConsumidos.amenity'
        ])->findOrFail($id);

        // Obtener análisis de fotos si existen
        $analisisFotos = PhotoAnalysis::where('limpieza_id', $id)
            ->with(['categoria'])
            ->orderBy('fecha_analisis', 'desc')
            ->get();

        // Obtener fotos de la limpieza
        if ($limpieza->tarea_asignada_id) {
            // Nueva lógica: obtener fotos desde ApartamentoLimpiezaItem (donde se guardan las fotos)
            $fotos = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $id)
                ->whereNotNull('photo_url')
                ->orderBy('created_at', 'desc')
                ->get();
        } else {
            // Lógica antigua
            $fotos = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $id)
                ->whereNotNull('photo_url')
                ->orderBy('created_at', 'desc')
                ->get();
        }

        // Obtener estadísticas de la limpieza
        $estadisticas = $this->obtenerEstadisticasLimpieza($id);

        // Obtener items únicos organizados por categoría (eliminando duplicados)
        $itemsUnicos = $this->obtenerItemsUnicosOrganizados($id);

        // Obtener estadísticas de amenities
        $estadisticasAmenities = $this->obtenerEstadisticasAmenities($id);
        
        return view('admin.limpiezas.show', compact(
            'limpieza', 
            'analisisFotos', 
            'estadisticas',
            'itemsUnicos',
            'fotos',
            'estadisticasAmenities'
        ));
    }

    /**
     * [2026-04-17] Marca una limpieza como "No realizada".
     * Se usa cuando la reserva fue no-show o la estancia se prolongo sin
     * necesidad de limpieza. Status 4 = "No realizada" (creado en seed).
     */
    public function marcarNoRealizada($id)
    {
        $limpieza = ApartamentoLimpieza::findOrFail($id);

        // Verificar que el estado destino existe
        $estadoNoRealizada = \App\Models\ApartamentoLimpiezaEstado::where('nombre', 'No realizada')->first();
        if (!$estadoNoRealizada) {
            return redirect()->back()->with('error', 'El estado "No realizada" no esta configurado en la base de datos.');
        }

        $limpieza->status_id = $estadoNoRealizada->id;
        $limpieza->fecha_fin = $limpieza->fecha_fin ?? now();
        $limpieza->save();

        \Illuminate\Support\Facades\Log::info('Limpieza marcada como No realizada', [
            'limpieza_id' => $limpieza->id,
            'user_id' => auth()->id(),
        ]);

        return redirect()->route('limpiezas.show', $limpieza->id)
            ->with('success', 'Limpieza marcada como no realizada correctamente.');
    }

    public function estadisticas()
    {
        // Estadísticas generales
        $totalLimpiezas = ApartamentoLimpieza::count();
        $limpiezasHoy = ApartamentoLimpieza::whereDate('fecha_comienzo', today())->count();
        $limpiezasSemana = ApartamentoLimpieza::whereBetween('fecha_comienzo', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        // Estadísticas por estado
        $estadosStats = DB::table('apartamento_limpieza')
            ->join('apartamento_estado', 'apartamento_limpieza.status_id', '=', 'apartamento_estado.id')
            ->select('apartamento_estado.nombre', DB::raw('count(*) as total'))
            ->groupBy('apartamento_estado.id', 'apartamento_estado.nombre')
            ->get();

        // Empleadas más activas
        $empleadasActivas = ApartamentoLimpieza::with('empleada')
            ->select('empleada_id', DB::raw('count(*) as total'))
            ->whereNotNull('empleada_id')
            ->groupBy('empleada_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Estadísticas de análisis de fotos
        $analisisStats = PhotoAnalysis::select(
            DB::raw('count(*) as total'),
            DB::raw('count(case when cumple_estandares = 1 then 1 end) as aprobadas'),
            DB::raw('count(case when cumple_estandares = 0 then 1 end) as rechazadas'),
            DB::raw('count(case when continuo_bajo_responsabilidad = 1 then 1 end) as bajo_responsabilidad')
        )->first();

        return response()->json([
            'total_limpiezas' => $totalLimpiezas,
            'limpiezas_hoy' => $limpiezasHoy,
            'limpiezas_semana' => $limpiezasSemana,
            'estados_stats' => $estadosStats,
            'empleadas_activas' => $empleadasActivas,
            'analisis_stats' => $analisisStats
        ]);
    }

    public function obtenerEstadisticasAmenities($limpiezaId)
    {
        $amenitiesConsumidos = \App\Models\AmenityConsumo::where('limpieza_id', $limpiezaId)
            ->with('amenity')
            ->get();
        
        $estadisticas = [
            'total_amenities' => $amenitiesConsumidos->count(),
            'total_costo' => $amenitiesConsumidos->sum('costo_total'),
            'categorias' => $amenitiesConsumidos->groupBy('amenity.categoria')->map(function($amenities) {
                return [
                    'cantidad' => $amenities->count(),
                    'costo' => $amenities->sum('costo_total'),
                    'items' => $amenities->map(function($amenity) {
                        return [
                            'nombre' => $amenity->amenity->nombre,
                            'cantidad_consumida' => $amenity->cantidad_consumida,
                            'cantidad_actual' => $amenity->cantidad_actual,
                            'costo_total' => $amenity->costo_total,
                            'observaciones' => $amenity->observaciones,
                            'fecha_consumo' => $amenity->fecha_consumo
                        ];
                    })
                ];
            })
        ];
        
        return $estadisticas;
    }
    
    public function obtenerEstadisticasLimpieza($limpiezaId)
    {
        $limpieza = \App\Models\ApartamentoLimpieza::find($limpiezaId);
        
        if ($limpieza && $limpieza->tarea_asignada_id) {
            // Nueva lógica: usar tarea_checklist_completados
            $itemsMarcados = \App\Models\TareaChecklistCompletado::where('tarea_asignada_id', $limpieza->tarea_asignada_id)
                ->whereNotNull('item_checklist_id')
                ->where('estado', 1)
                ->count();
            
            // Calcular total de items disponibles para esta tarea
            $tarea = \App\Models\TareaAsignada::find($limpieza->tarea_asignada_id);
            $totalItems = 0;
            if ($tarea && $tarea->apartamento_id) {
                $edificioId = $tarea->apartamento->edificio_id;
                $checklists = \App\Models\Checklist::where('edificio_id', $edificioId)->with('items')->get();
                $totalItems = $checklists->sum(function($checklist) {
                    return $checklist->items->count();
                });
            }
        } else {
            // Lógica antigua: usar ApartamentoLimpiezaItem
            $itemsMarcados = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
                ->where('estado', 1)
                ->pluck('item_id')
                ->filter()
                ->unique()
                ->count();

            $totalItems = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
                ->pluck('item_id')
                ->filter()
                ->unique()
                ->count();
        }

        // Obtener fotos de la limpieza
        $totalFotos = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
            ->whereNotNull('photo_url')
            ->count();

        // Obtener análisis de fotos
        $analisisFotos = PhotoAnalysis::where('limpieza_id', $limpiezaId)->get();
        $fotosAprobadas = $analisisFotos->where('cumple_estandares', true)->count();
        $fotosRechazadas = $analisisFotos->where('cumple_estandares', false)->count();
        $bajoResponsabilidad = $analisisFotos->where('continuo_bajo_responsabilidad', true)->count();

        // Calcular puntuación media
        $puntuacionMedia = $analisisFotos->count() > 0 ? $analisisFotos->avg('puntuacion') : 0;

        return [
            'items_marcados' => $itemsMarcados,
            'total_items' => $totalItems,
            'porcentaje_completado' => $totalItems > 0 ? round(($itemsMarcados / $totalItems) * 100, 2) : 0,
            'fotos_aprobadas' => $fotosAprobadas,
            'fotos_rechazadas' => $fotosRechazadas,
            'bajo_responsabilidad' => $bajoResponsabilidad,
            'puntuacion_media' => round($puntuacionMedia, 1),
            'total_fotos' => $totalFotos
        ];
    }

    private function obtenerItemsUnicosOrganizados($limpiezaId)
    {
        $limpieza = \App\Models\ApartamentoLimpieza::find($limpiezaId);
        
        if ($limpieza && $limpieza->tarea_asignada_id) {
            // Nueva lógica: obtener TODOS los items de los checklists del edificio
            $tarea = \App\Models\TareaAsignada::find($limpieza->tarea_asignada_id);
            if ($tarea && $tarea->apartamento_id) {
                $edificioId = $tarea->apartamento->edificio_id;
                $checklists = \App\Models\Checklist::where('edificio_id', $edificioId)
                    ->with(['items'])
                    ->get();
                
                // Obtener items completados para marcar cuáles están hechos
                $itemsCompletados = \App\Models\TareaChecklistCompletado::where('tarea_asignada_id', $limpieza->tarea_asignada_id)
                    ->whereNotNull('item_checklist_id')
                    ->where('estado', 1)
                    ->pluck('item_checklist_id')
                    ->toArray();
                
                // Crear estructura con todos los items y su estado
                $items = collect();
                foreach ($checklists as $checklist) {
                    foreach ($checklist->items as $item) {
                        $itemData = (object) [
                            'id' => $item->id,
                            'nombre' => $item->nombre,
                            'checklist_id' => $checklist->id,
                            'checklist_nombre' => $checklist->nombre,
                            'estado' => in_array($item->id, $itemsCompletados) ? 1 : 0,
                            'completado_por' => null,
                            'fecha_completado' => null
                        ];
                        $items->push($itemData);
                    }
                }
                
                // Agrupar por nombre de checklist con formato más descriptivo
                $items = $items->groupBy(function($item) {
                    return $item->checklist_nombre;
                });
            } else {
                $items = collect();
            }
        } else {
            // Lógica antigua: usar ApartamentoLimpiezaItem
            $items = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $limpiezaId)
                ->whereNotNull('item_id')
                ->where('item_id', '!=', '')
                ->with(['item.checklist'])
                ->get()
                ->groupBy('item.checklist.nombre')
                ->map(function($grupo) {
                    return $grupo->unique('item_id')->values();
                });
        }

        return [
            'items' => $items,
            'checklists' => collect([])
        ];
    }
}
