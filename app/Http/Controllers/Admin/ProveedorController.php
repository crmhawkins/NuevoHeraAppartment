<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $proveedores = Proveedor::withCount('articulos')
            ->withSum('articulos', 'stock_actual')
            ->orderBy('nombre')
            ->paginate(15);

        return view('admin.proveedores.index', compact('proveedores'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.proveedores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'cif_nif' => 'nullable|string|max:20',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        try {
            Proveedor::create($request->all());

            return redirect()->route('admin.proveedores.index')
                ->with('swal_success', 'Proveedor creado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Proveedor $proveedor)
    {
        $proveedor->load(['articulos' => function($query) {
            $query->orderBy('nombre');
        }]);

        $estadisticas = [
            'total_articulos' => $proveedor->articulos->count(),
            'stock_total' => $proveedor->articulos->sum('stock_actual'),
            'stock_bajo' => $proveedor->articulos->where('stock_actual', '<=', 'stock_minimo')->count(),
            'valor_total' => $proveedor->articulos->sum(function($articulo) {
                return $articulo->stock_actual * $articulo->precio_compra;
            })
        ];

        return view('admin.proveedores.show', compact('proveedor', 'estadisticas'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Proveedor $proveedor)
    {
        return view('admin.proveedores.edit', compact('proveedor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'contacto' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'direccion' => 'nullable|string',
            'cif_nif' => 'nullable|string|max:20',
            'observaciones' => 'nullable|string',
            'activo' => 'boolean'
        ]);

        try {
            $proveedor->update($request->all());

            return redirect()->route('admin.proveedores.index')
                ->with('swal_success', 'Proveedor actualizado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el proveedor: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Proveedor $proveedor)
    {
        try {
            // Verificar si tiene artículos asociados
            if ($proveedor->articulos()->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el proveedor porque tiene artículos asociados.');
            }

            $proveedor->delete();

            return redirect()->route('admin.proveedores.index')
                ->with('swal_success', 'Proveedor eliminado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el proveedor: ' . $e->getMessage());
        }
    }
}
