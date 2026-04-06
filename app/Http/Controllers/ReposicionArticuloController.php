<?php

namespace App\Http\Controllers;

use App\Models\ReposicionArticulo;
use App\Models\Articulo;
use App\Models\ItemChecklist;
use App\Models\ApartamentoLimpieza;
use App\Models\MovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ReposicionArticuloController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'apartamento_limpieza_id' => 'required|exists:apartamento_limpieza,id',
            'item_checklist_id' => 'required|exists:items_checklists,id',
            'cantidad_reponer' => 'required|numeric|min:0.01',
            'observaciones' => 'nullable|string|max:500'
        ]);

        try {
            DB::beginTransaction();

            $itemChecklist = ItemChecklist::with('articulo')->findOrFail($request->item_checklist_id);
            $articulo = $itemChecklist->articulo;
            $apartamentoLimpieza = ApartamentoLimpieza::findOrFail($request->apartamento_limpieza_id);

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
                'apartamento_limpieza_id' => $request->apartamento_limpieza_id,
                'item_checklist_id' => $request->item_checklist_id,
                'articulo_id' => $articulo->id,
                'user_id' => Auth::id(),
                'cantidad_reponer' => $cantidadReponer,
                'cantidad_anterior' => 0, // Se puede implementar lógica para obtener cantidad anterior
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
                    'referencia' => "Reposición #{$reposicion->id}",
                    'user_id' => Auth::id(),
                    'apartamento_limpieza_id' => $request->apartamento_limpieza_id
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reposición registrada correctamente',
                'data' => [
                    'reposicion' => $reposicion,
                    'articulo' => $articulo->fresh(),
                    'stock_descontado' => $stockDescontado,
                    'tipo_descuento' => $articulo->tipo_descuento
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la reposición: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReposiciones($apartamentoLimpiezaId)
    {
        $reposiciones = ReposicionArticulo::with(['articulo', 'itemChecklist', 'user'])
            ->where('apartamento_limpieza_id', $apartamentoLimpiezaId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reposiciones
        ]);
    }
}