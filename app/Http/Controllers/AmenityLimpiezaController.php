<?php

namespace App\Http\Controllers;

use App\Models\Amenity;
use App\Models\AmenityConsumo;
use App\Models\ApartamentoLimpieza;
use App\Models\Reserva;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\AmenityConsumptionService;

class AmenityLimpiezaController extends Controller
{
    /**
     * Mostrar la vista de gestión de amenities para una limpieza
     */
    public function show(Request $request, $limpiezaId)
    {
        $limpieza = ApartamentoLimpieza::with(['apartamento', 'reserva'])->findOrFail($limpiezaId);
        $apartamento = $limpieza->apartamento;
        $reserva = $limpieza->reserva;
        
        // Obtener amenities activos por categoría
        $amenities = Amenity::activos()
            ->orderBy('categoria')
            ->orderBy('nombre')
            ->get()
            ->groupBy('categoria');
        
        // Obtener consumos existentes para esta limpieza
        $consumosExistentes = AmenityConsumo::where('limpieza_id', $limpiezaId)
            ->with('amenity')
            ->get()
            ->keyBy('amenity_id');
        
        // Calcular cantidades recomendadas para cada amenity
        $amenitiesConRecomendaciones = [];
        foreach ($amenities as $categoria => $amenitiesCategoria) {
            foreach ($amenitiesCategoria as $amenity) {
                $cantidadRecomendada = $this->calcularCantidadRecomendada($amenity, $reserva, $apartamento);
                $consumoExistente = $consumosExistentes->get($amenity->id);
                
                $amenitiesConRecomendaciones[$categoria][] = [
                    'amenity' => $amenity,
                    'cantidad_recomendada' => $cantidadRecomendada,
                    'consumo_existente' => $consumoExistente,
                    'stock_disponible' => $amenity->stock_actual
                ];
            }
        }
        
        // Si es una petición AJAX, devolver solo el contenido del formulario
        if ($request->ajax()) {
            return view('admin.amenities.limpieza-form', compact(
                'limpieza',
                'apartamento', 
                'reserva',
                'amenitiesConRecomendaciones',
                'consumosExistentes'
            ));
        }
        
        return view('admin.amenities.limpieza-gestion', compact(
            'limpieza',
            'apartamento', 
            'reserva',
            'amenitiesConRecomendaciones',
            'consumosExistentes'
        ));
    }
    
    /**
     * Guardar el consumo de amenities
     */
    public function store(Request $request, $limpiezaId)
    {
        $request->validate([
            'amenities' => 'required|array',
            'amenities.*.amenity_id' => 'required|exists:amenities,id',
            'amenities.*.cantidad_dejada' => 'required|integer|min:0',
            'amenities.*.observaciones' => 'nullable|string|max:500'
        ]);
        
        $limpieza = ApartamentoLimpieza::findOrFail($limpiezaId);
        
        DB::beginTransaction();
        try {
            foreach ($request->amenities as $amenityData) {
                // Usar lockForUpdate para evitar condiciones de carrera
                $amenity = Amenity::lockForUpdate()->find($amenityData['amenity_id']);
                if (!$amenity) {
                    throw new \Exception("Amenity con ID {$amenityData['amenity_id']} no encontrado");
                }
                
                // Para amenities tipo "por_reserva", usar consumo_por_reserva en lugar de cantidad_dejada
                if ($amenity->tipo_consumo === 'por_reserva') {
                    // Usar consumo_por_reserva configurado
                    $cantidadDejada = $amenity->consumo_por_reserva ?? 0;
                } else {
                    // Para otros tipos, usar cantidad_dejada manual
                    $cantidadDejada = floatval($amenityData['cantidad_dejada'] ?? 0);
                }
                
                if ($cantidadDejada <= 0) {
                    continue; // Saltar si no hay cantidad válida
                }
                
                // Verificar si ya existe un consumo para este amenity en esta limpieza
                $consumoExistente = AmenityConsumo::where('limpieza_id', $limpiezaId)
                    ->where('amenity_id', $amenity->id)
                    ->first();
                
                if ($consumoExistente) {
                    // ACTUALIZAR el consumo existente usando ajustarStock()
                    // Refrescar el modelo para obtener el stock actualizado
                    $amenity->refresh();
                    
                    // Stock antes del ajuste
                    $stockAnterior = $amenity->stock_actual;
                    $cantidadConsumoAnterior = $consumoExistente->cantidad_consumida;
                    
                    // Ajustar el stock basado en la diferencia de consumo
                    $stockActual = $amenity->ajustarStock($cantidadConsumoAnterior, $cantidadDejada);
                    
                    // Calcular costo
                    $costoTotal = $cantidadDejada * $amenity->precio_compra;
                    
                    // Actualizar el consumo con los valores reales del stock
                    $consumoExistente->update([
                        'cantidad_consumida' => $cantidadDejada,
                        'cantidad_anterior' => $stockAnterior,
                        'cantidad_actual' => $stockActual,
                        'costo_unitario' => $amenity->precio_compra,
                        'costo_total' => $costoTotal,
                        'observaciones' => $amenityData['observaciones'] ?? null,
                        'fecha_consumo' => now()
                    ]);
                } else {
                    // Crear nuevo consumo - usar método estándar para descontar stock
                    try {
                        // Refrescar el modelo para obtener el stock más reciente
                        $amenity->refresh();
                        
                        $resultadoDescuento = $amenity->descontarStock($cantidadDejada);
                        $costoTotal = $cantidadDejada * $amenity->precio_compra;
                        
                        AmenityConsumo::create([
                            'amenity_id' => $amenity->id,
                            'reserva_id' => $limpieza->reserva_id,
                            'apartamento_id' => $limpieza->apartamento_id,
                            'limpieza_id' => $limpiezaId,
                            'user_id' => auth()->id(),
                            'tipo_consumo' => $amenity->tipo_consumo,
                            'cantidad_consumida' => $cantidadDejada,
                            'cantidad_anterior' => $resultadoDescuento['stock_anterior'],
                            'cantidad_actual' => $resultadoDescuento['stock_actual'],
                            'costo_unitario' => $amenity->precio_compra,
                            'costo_total' => $costoTotal,
                            'observaciones' => $amenityData['observaciones'] ?? null,
                            'fecha_consumo' => now()
                        ]);
                    } catch (\Exception $e) {
                        throw new \Exception("Error con amenity '{$amenity->nombre}': {$e->getMessage()}");
                    }
                }
            }
            
            DB::commit();
            
            return redirect()->back()->with('swal_success', 'Amenities actualizados correctamente');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('swal_error', 'Error al actualizar amenities: ' . $e->getMessage());
        }
    }
    
    /**
     * Calcular cantidad recomendada para un amenity según las reglas de consumo
     */
    private function calcularCantidadRecomendada($amenity, $reserva, $apartamento)
    {
        return AmenityConsumptionService::calculateRecommendedQuantity($amenity, $reserva, $apartamento);
    }
    
    /**
     * Obtener historial de consumos de un amenity
     */
    public function historial($amenityId)
    {
        $amenity = Amenity::findOrFail($amenityId);
        $consumos = AmenityConsumo::where('amenity_id', $amenityId)
            ->with(['reserva', 'apartamento', 'limpieza', 'user'])
            ->orderBy('fecha_consumo', 'desc')
            ->paginate(20);
            
        return view('admin.amenities.historial', compact('amenity', 'consumos'));
    }
    
    /**
     * Obtener amenities necesarios para una reserva específica
     */
    public function getAmenitiesReserva($reservaId)
    {
        try {
            $reserva = Reserva::with(['apartamento'])->findOrFail($reservaId);
            
            // Obtener solo amenities activos de categoría "Otros" para el modal de limpiadoras
            $amenities = Amenity::activos()
                ->where('categoria', 'Otros')
                ->orderBy('categoria')
                ->orderBy('nombre')
                ->get()
                ->groupBy('categoria');
            
            // Calcular cantidades recomendadas para cada amenity
            $amenitiesConRecomendaciones = [];
            foreach ($amenities as $categoria => $amenitiesCategoria) {
                foreach ($amenitiesCategoria as $amenity) {
                    $cantidadRecomendada = $this->calcularCantidadRecomendada($amenity, $reserva, $reserva->apartamento);
                    
                    $amenitiesConRecomendaciones[$categoria][] = [
                        'amenity' => $amenity,
                        'cantidad_recomendada' => $cantidadRecomendada,
                        'stock_disponible' => $amenity->stock_actual,
                        'precio_unitario' => $amenity->precio_compra,
                        'tipo_consumo' => $amenity->tipo_consumo
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'reserva' => [
                    'id' => $reserva->id,
                    'apartamento' => $reserva->apartamento->titulo ?? $reserva->apartamento->nombre,
                    'numero_personas' => $reserva->numero_personas,
                    'fecha_entrada' => $reserva->fecha_entrada,
                    'fecha_salida' => $reserva->fecha_salida,
                    'dias' => Carbon::parse($reserva->fecha_entrada)->diffInDays($reserva->fecha_salida)
                ],
                'amenities' => $amenitiesConRecomendaciones
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar amenities: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener amenities de una limpieza completada
     */
    public function getAmenitiesLimpiezaCompletada($limpiezaId)
    {
        try {
            $limpieza = ApartamentoLimpieza::with(['apartamento', 'reserva'])->findOrFail($limpiezaId);
            
            if (!$limpieza->apartamento) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta limpieza no corresponde a un apartamento'
                ], 400);
            }
            
            // Obtener amenities activos por categoría
            $amenities = Amenity::activos()
                ->orderBy('categoria')
                ->orderBy('nombre')
                ->get()
                ->groupBy('categoria');
            
            // Obtener consumos reales registrados para esta limpieza
            $consumosReales = AmenityConsumo::where('limpieza_id', $limpiezaId)
                ->with('amenity')
                ->get()
                ->keyBy('amenity_id');
            
            // Calcular cantidades recomendadas y comparar con consumos reales
            $amenitiesConEstado = [];
            $totalCosto = 0;
            $amenitiesProporcionados = 0;
            $amenitiesFaltantes = 0;
            
            foreach ($amenities as $categoria => $amenitiesCategoria) {
                foreach ($amenitiesCategoria as $amenity) {
                    $cantidadRecomendada = $this->calcularCantidadRecomendada($amenity, $limpieza->reserva, $limpieza->apartamento);
                    $consumoReal = $consumosReales->get($amenity->id);
                    $cantidadReal = $consumoReal ? (float) $consumoReal->cantidad_consumida : 0;
                    
                    // Determinar estado del amenity
                    $estado = 'completo';
                    if ($cantidadReal == 0) {
                        $estado = 'faltante';
                        $amenitiesFaltantes++;
                    } elseif ($cantidadReal < $cantidadRecomendada) {
                        $estado = 'incompleto';
                        $amenitiesFaltantes++;
                    } else {
                        $amenitiesProporcionados++;
                    }
                    
                    // Calcular costo
                    $costoAmenity = $cantidadReal * $amenity->precio_compra;
                    $totalCosto += $costoAmenity;
                    
                    $amenitiesConEstado[$categoria][] = [
                        'amenity' => $amenity,
                        'cantidad_recomendada' => $cantidadRecomendada,
                        'cantidad_real' => $cantidadReal,
                        'estado' => $estado,
                        'precio_unitario' => $amenity->precio_compra,
                        'costo_total' => $costoAmenity,
                        'tipo_consumo' => $amenity->tipo_consumo,
                        'observaciones' => $consumoReal ? $consumoReal->observaciones : null,
                        'fecha_consumo' => $consumoReal ? $consumoReal->fecha_consumo : null
                    ];
                }
            }
            
            return response()->json([
                'success' => true,
                'limpieza' => [
                    'id' => $limpieza->id,
                    'apartamento' => $limpieza->apartamento->nombre,
                    'fecha_comienzo' => $limpieza->fecha_comienzo,
                    'fecha_fin' => $limpieza->fecha_fin,
                    'empleado' => $limpieza->empleado ? $limpieza->empleado->name : 'No asignado'
                ],
                'amenities' => $amenitiesConEstado,
                'resumen' => [
                    'total_amenities' => count($amenities->flatten()),
                    'proporcionados' => $amenitiesProporcionados,
                    'faltantes' => $amenitiesFaltantes,
                    'costo_total' => round($totalCosto, 2)
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar amenities de la limpieza: ' . $e->getMessage()
            ], 500);
        }
    }
}
