<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Articulo;
use App\Models\Proveedor;
use App\Models\MovimientoStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArticuloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Articulo::with('proveedor');

        // Filtros
        if ($request->filled('proveedor_id')) {
            $query->where('proveedor_id', $request->proveedor_id);
        }

        if ($request->filled('categoria')) {
            $query->where('categoria', $request->categoria);
        }

        if ($request->filled('stock')) {
            switch ($request->stock) {
                case 'con_stock':
                    $query->where('stock_actual', '>', 0);
                    break;
                case 'sin_stock':
                    $query->where('stock_actual', '=', 0);
                    break;
                case 'stock_bajo':
                    $query->whereColumn('stock_actual', '<=', 'stock_minimo');
                    break;
            }
        }

        if ($request->filled('activo')) {
            $query->where('activo', $request->activo);
        }

        $articulos = $query->orderBy('nombre')->paginate(15)->appends($request->except('page'));

        // Datos para filtros
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        $categorias = Articulo::distinct()->pluck('categoria')->filter()->sort()->values();

        $estadisticas = [
            'total_articulos' => Articulo::count(),
            'stock_bajo' => Articulo::stockBajo()->count(),
            'valor_total' => Articulo::sum(DB::raw('stock_actual * precio_compra'))
        ];

        return view('admin.articulos.index', compact('articulos', 'proveedores', 'categorias', 'estadisticas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        return view('admin.articulos.create', compact('proveedores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string|max:255',
            'unidad_medida' => 'required|string|max:50',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'stock_maximo' => 'nullable|numeric|min:0',
            'precio_compra' => 'required|numeric|min:0',
            'codigo_producto' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean',
            'proveedor_id' => 'required|exists:proveedors,id'
        ], [
            'nombre.required' => 'El nombre del artículo es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'stock_actual.required' => 'El stock inicial es obligatorio.',
            'stock_actual.numeric' => 'El stock inicial debe ser un número válido.',
            'stock_actual.min' => 'El stock inicial no puede ser negativo.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número válido.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_maximo.numeric' => 'El stock máximo debe ser un número válido.',
            'stock_maximo.min' => 'El stock máximo no puede ser negativo.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número válido.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.'
        ]);

        try {
            DB::beginTransaction();

            $articulo = Articulo::create($request->only(['nombre', 'descripcion', 'categoria', 'unidad_medida', 'stock_actual', 'stock_minimo', 'stock_maximo', 'precio_compra', 'codigo_producto', 'observaciones', 'activo', 'proveedor_id']));

            // Si hay stock inicial, crear movimiento de entrada
            if ($articulo->stock_actual > 0) {
                MovimientoStock::crearEntrada(
                    $articulo->id,
                    $articulo->stock_actual,
                    $articulo->precio_compra,
                    'stock_inicial',
                    'Stock inicial al crear el artículo',
                    $articulo->proveedor_id
                );
            }

            DB::commit();

            return redirect()->route('admin.articulos.index')
                ->with('swal_success', 'Artículo creado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el artículo: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Articulo $articulo)
    {
        $articulo->load(['proveedor', 'movimientosStock' => function($query) {
            $query->orderBy('fecha_movimiento', 'desc')->limit(20);
        }]);

        $estadisticas = [
            'total_movimientos' => $articulo->movimientosStock()->count(),
            'entradas_totales' => $articulo->movimientosStock()->entradas()->sum('cantidad'),
            'salidas_totales' => $articulo->movimientosStock()->salidas()->sum('cantidad'),
            'valor_actual' => $articulo->stock_actual * $articulo->precio_compra
        ];

        return view('admin.articulos.show', compact('articulo', 'estadisticas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Articulo $articulo)
    {
        $proveedores = Proveedor::activos()->orderBy('nombre')->get();
        
        // Cargar los movimientos de stock ordenados por fecha descendente
        $articulo->load(['movimientosStock' => function($query) {
            $query->orderBy('fecha_movimiento', 'desc')->limit(10);
        }]);
        
        return view('admin.articulos.edit', compact('articulo', 'proveedores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Articulo $articulo)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string|max:255',
            'unidad_medida' => 'required|string|max:50',
            'stock_actual' => 'required|numeric|min:0',
            'stock_minimo' => 'required|numeric|min:0',
            'stock_maximo' => 'nullable|numeric|min:0',
            'precio_compra' => 'required|numeric|min:0',
            'codigo_producto' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean',
            'proveedor_id' => 'required|exists:proveedors,id'
        ], [
            'nombre.required' => 'El nombre del artículo es obligatorio.',
            'categoria.required' => 'La categoría es obligatoria.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'stock_actual.required' => 'El stock actual es obligatorio.',
            'stock_actual.numeric' => 'El stock actual debe ser un número válido.',
            'stock_actual.min' => 'El stock actual no puede ser negativo.',
            'stock_minimo.required' => 'El stock mínimo es obligatorio.',
            'stock_minimo.numeric' => 'El stock mínimo debe ser un número válido.',
            'stock_minimo.min' => 'El stock mínimo no puede ser negativo.',
            'stock_maximo.numeric' => 'El stock máximo debe ser un número válido.',
            'stock_maximo.min' => 'El stock máximo no puede ser negativo.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número válido.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'proveedor_id.required' => 'Debe seleccionar un proveedor.',
            'proveedor_id.exists' => 'El proveedor seleccionado no existe.'
        ]);

        try {
            DB::beginTransaction();

            $stockAnterior = $articulo->stock_actual;
            $articulo->update($request->all());

            // Si cambió el stock, crear movimiento de ajuste
            if ($stockAnterior != $articulo->stock_actual) {
                MovimientoStock::crearAjuste(
                    $articulo->id,
                    $articulo->stock_actual,
                    'ajuste_manual',
                    'Ajuste manual desde edición'
                );
            }

            DB::commit();

            return redirect()->route('admin.articulos.index')
                ->with('swal_success', 'Artículo actualizado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el artículo: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Articulo $articulo)
    {
        try {
            // Verificar si tiene movimientos o está asociado a items
            if ($articulo->movimientosStock()->count() > 0 || $articulo->itemChecklists()->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el artículo porque tiene movimientos o está asociado a items de apartamento.');
            }

            $articulo->delete();

            return redirect()->route('admin.articulos.index')
                ->with('swal_success', 'Artículo eliminado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el artículo: ' . $e->getMessage());
        }
    }

    /**
     * Reponer stock del artículo
     */
    public function reponerStock(Request $request, Articulo $articulo)
    {
        $request->validate([
            'cantidad' => 'required|numeric|min:0.01',
            'precio_unitario' => 'nullable|numeric|min:0',
            'motivo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
            'numero_factura' => 'nullable|string|max:100'
        ]);

        try {
            DB::beginTransaction();

            MovimientoStock::crearEntrada(
                $articulo->id,
                $request->cantidad,
                $request->precio_unitario,
                $request->motivo ?? 'reposicion',
                $request->observaciones,
                $articulo->proveedor_id,
                $request->numero_factura
            );

            DB::commit();

            return redirect()->back()
                ->with('swal_success', 'Stock repuesto correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('swal_error', 'Error al reponer stock: ' . $e->getMessage());
        }
    }
}
