<?php

namespace App\Http\Controllers;

use App\Models\ApartamentoLimpieza;
use App\Models\ItemChecklist;
use App\Models\Articulo;
use App\Models\ReposicionArticulo;
use App\Models\Incidencia;
use App\Models\MovimientoStock;
use App\Services\AlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class LimpiezaAccionesController extends Controller
{
    /**
     * Constructor del controlador
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Reponer stock de un artículo desde el checklist
     */
    public function reponerStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apartamento_limpieza_id' => 'required|exists:apartamento_limpieza,id',
            'item_checklist_id' => 'required|exists:items_checklists,id',
            'cantidad_reponer' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:500'
        ], [
            'apartamento_limpieza_id.required' => 'ID de limpieza es obligatorio',
            'apartamento_limpieza_id.exists' => 'La limpieza no existe',
            'item_checklist_id.required' => 'ID del item es obligatorio',
            'item_checklist_id.exists' => 'El item no existe',
            'cantidad_reponer.required' => 'La cantidad a reponer es obligatoria',
            'cantidad_reponer.numeric' => 'La cantidad debe ser un número',
            'cantidad_reponer.min' => 'La cantidad debe ser mayor a 0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $itemChecklist = ItemChecklist::with('articulo')->findOrFail($request->item_checklist_id);
            $articulo = $itemChecklist->articulo;
            $apartamentoLimpieza = ApartamentoLimpieza::with(['apartamento', 'zonaComun'])->findOrFail($request->apartamento_limpieza_id);

            if (!$articulo) {
                return response()->json([
                    'success' => false,
                    'message' => 'El item no tiene un artículo asociado'
                ], 400);
            }

            $cantidadReponer = $request->cantidad_reponer;
            $stockAnterior = $articulo->stock_actual;
            $stockDescontado = false;

            // Aplicar la lógica de descuento según el tipo
            if ($articulo->esTipoConsumo()) {
                // Para artículos de consumo (cubiertos, vajilla), descontar del stock
                $articulo->descontarStock($cantidadReponer);
                $stockDescontado = true;
            }
            // Para artículos de reposición (toallas, sábanas), no se descuenta del stock

            // Crear el registro de reposición
            $reposicion = ReposicionArticulo::create([
                'apartamento_limpieza_id' => $apartamentoLimpieza->id,
                'item_checklist_id' => $itemChecklist->id,
                'articulo_id' => $articulo->id,
                'user_id' => Auth::id(),
                'cantidad_reponer' => $cantidadReponer,
                'cantidad_anterior' => 0, // Se puede implementar lógica para trackear cantidad anterior
                'cantidad_nueva' => $cantidadReponer,
                'tipo_descuento' => $articulo->tipo_descuento,
                'stock_descontado' => $stockDescontado,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $articulo->stock_actual,
                'observaciones' => $request->observaciones
            ]);

            // Crear movimiento de stock si se descontó
            if ($stockDescontado) {
                MovimientoStock::create([
                    'articulo_id' => $articulo->id,
                    'tipo_movimiento' => 'salida',
                    'cantidad' => $cantidadReponer,
                    'motivo' => 'Reposición en limpieza',
                    'referencia' => "Limpieza #{$apartamentoLimpieza->id} - Item: {$itemChecklist->nombre}",
                    'user_id' => Auth::id(),
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $articulo->stock_actual
                ]);
            }

            // Log de la acción
            Log::info('Reposición de stock realizada', [
                'user_id' => Auth::id(),
                'apartamento_limpieza_id' => $apartamentoLimpieza->id,
                'item_checklist_id' => $itemChecklist->id,
                'articulo_id' => $articulo->id,
                'cantidad_reponer' => $cantidadReponer,
                'stock_descontado' => $stockDescontado
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock repuesto correctamente',
                'data' => [
                    'reposicion_id' => $reposicion->id,
                    'stock_actual' => $articulo->stock_actual,
                    'stock_descontado' => $stockDescontado,
                    'tipo_descuento' => $articulo->tipo_descuento
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al reponer stock: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reponer el stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reportar avería de un item del checklist
     */
    public function reportarAveria(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'apartamento_limpieza_id' => 'required|exists:apartamento_limpieza,id',
            'item_checklist_id' => 'required|exists:items_checklists,id',
            'descripcion' => 'required|string|max:1000',
            'prioridad' => 'required|in:baja,media,alta,urgente'
        ], [
            'apartamento_limpieza_id.required' => 'ID de limpieza es obligatorio',
            'apartamento_limpieza_id.exists' => 'La limpieza no existe',
            'item_checklist_id.required' => 'ID del item es obligatorio',
            'item_checklist_id.exists' => 'El item no existe',
            'descripcion.required' => 'La descripción de la avería es obligatoria',
            'descripcion.max' => 'La descripción no puede superar los 1000 caracteres',
            'prioridad.required' => 'La prioridad es obligatoria',
            'prioridad.in' => 'Prioridad inválida'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $itemChecklist = ItemChecklist::findOrFail($request->item_checklist_id);
            $apartamentoLimpieza = ApartamentoLimpieza::with(['apartamento', 'zonaComun'])->findOrFail($request->apartamento_limpieza_id);

            // Verificar que el item tiene la opción de averías
            if (!$itemChecklist->tiene_averias) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este item no permite reportar averías'
                ], 400);
            }

            // Determinar el tipo y elemento de la incidencia
            $tipo = $apartamentoLimpieza->tipo_limpieza === 'zona_comun' ? 'zona_comun' : 'apartamento';
            $apartamentoId = $tipo === 'apartamento' ? $apartamentoLimpieza->apartamento_id : null;
            $zonaComunId = $tipo === 'zona_comun' ? $apartamentoLimpieza->zona_comun_id : null;

            // Crear la incidencia
            $incidencia = Incidencia::create([
                'titulo' => "Avería en {$itemChecklist->nombre}",
                'descripcion' => $request->descripcion,
                'tipo' => $tipo,
                'apartamento_id' => $apartamentoId,
                'zona_comun_id' => $zonaComunId,
                'empleada_id' => Auth::id(),
                'apartamento_limpieza_id' => $apartamentoLimpieza->id,
                'prioridad' => $request->prioridad,
                'estado' => 'pendiente'
            ]);

            // Crear alerta para los administradores
            $elementoNombre = $apartamentoLimpieza->getElementoNombre();
            
            AlertService::createIncidentAlert(
                $incidencia->id,
                $incidencia->titulo,
                $tipo === 'apartamento' ? 'Apartamento' : 'Zona Común',
                $elementoNombre,
                $request->prioridad,
                Auth::user()->name
            );

            // Notificar automáticamente a técnicos (es reparación desde limpieza)
            if ($incidencia->apartamento_limpieza_id !== null) {
                try {
                    \App\Services\TecnicoNotificationService::notifyTechniciansAboutIncident($incidencia);
                    Log::info('Técnicos notificados automáticamente sobre la incidencia', [
                        'incidencia_id' => $incidencia->id
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error notificando técnicos automáticamente: ' . $e->getMessage());
                    // No fallar la creación de la incidencia si falla la notificación
                }
            }

            // Notificar equipo de gestion por WhatsApp
            try {
                \App\Services\AlertaEquipoService::alertar(
                    'INCIDENCIA LIMPIEZA - ' . strtoupper($request->prioridad ?? 'media'),
                    "Reportada por: " . Auth::user()->name . "\n"
                    . "Apartamento: " . $elementoNombre . "\n"
                    . "Descripción: " . $request->descripcion,
                    'incidencia_limpieza'
                );
            } catch (\Exception $e) {
                Log::error('Error WhatsApp incidencia limpieza: ' . $e->getMessage());
            }

            // Log de la acción
            Log::info('Avería reportada desde limpieza', [
                'user_id' => Auth::id(),
                'apartamento_limpieza_id' => $apartamentoLimpieza->id,
                'item_checklist_id' => $itemChecklist->id,
                'incidencia_id' => $incidencia->id,
                'prioridad' => $request->prioridad
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Avería reportada correctamente',
                'data' => [
                    'incidencia_id' => $incidencia->id,
                    'titulo' => $incidencia->titulo,
                    'prioridad' => $incidencia->prioridad,
                    'estado' => $incidencia->estado
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al reportar avería: ' . $e->getMessage(), [
                'request' => $request->all(),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al reportar la avería: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener información de un item para mostrar en el modal
     */
    public function getItemInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_checklist_id' => 'required',
            'tipo_item' => 'nullable|in:apartamento,zona_comun'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Parámetros no válidos'
            ], 422);
        }

        try {
            $itemId = $request->item_checklist_id;
            $tipoItem = $request->tipo_item ?? 'apartamento';
            
            if ($tipoItem === 'zona_comun') {
                $item = \App\Models\ItemChecklistZonaComun::with(['articulo', 'checklist'])->findOrFail($itemId);
            } else {
                $item = ItemChecklist::with(['articulo', 'checklist'])->findOrFail($itemId);
            }
            
            $data = [
                'id' => $item->id,
                'nombre' => $item->nombre,
                'tiene_stock' => $item->tiene_stock,
                'tiene_averias' => $item->tiene_averias,
                'cantidad_requerida' => $item->cantidad_requerida,
                'observaciones_stock' => $item->observaciones_stock,
                'tipo_item' => $tipoItem
            ];

            if ($item->articulo) {
                $data['articulo'] = [
                    'id' => $item->articulo->id,
                    'nombre' => $item->articulo->nombre,
                    'stock_actual' => $item->articulo->stock_actual,
                    'stock_minimo' => $item->articulo->stock_minimo,
                    'unidad_medida' => $item->articulo->unidad_medida,
                    'tipo_descuento' => $item->articulo->tipo_descuento,
                    'estado_stock' => $item->articulo->estado_stock,
                    'descripcion_tipo_descuento' => $item->articulo->descripcion_tipo_descuento
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error al obtener información del item: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información del item'
            ], 500);
        }
    }
}
