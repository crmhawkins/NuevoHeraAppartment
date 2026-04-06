<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaServicioTecnico;
use App\Models\ServicioTecnico;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class ServiciosTecnicosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categorias = CategoriaServicioTecnico::withCount('servicios')
            ->ordenadas()
            ->get();

        $servicios = ServicioTecnico::with('categoria')
            ->ordenados()
            ->get();

        // Agrupar servicios por categoría
        $serviciosPorCategoria = $servicios->groupBy('categoria_id');

        return view('admin.servicios-tecnicos.index', compact('categorias', 'servicios', 'serviciosPorCategoria'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = CategoriaServicioTecnico::activas()->ordenadas()->get();
        return view('admin.servicios-tecnicos.create', compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'categoria_id' => 'nullable|exists:categorias_servicios_tecnicos,id',
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'unidad_medida' => 'nullable|string|max:50',
            'precio_base' => 'nullable|numeric|min:0|max:999999.99',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        ServicioTecnico::create($validated);

        Alert::success('Éxito', 'Servicio técnico creado correctamente');
        return redirect()->route('admin.servicios-tecnicos.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServicioTecnico $serviciosTecnico)
    {
        $categorias = CategoriaServicioTecnico::activas()->ordenadas()->get();
        return view('admin.servicios-tecnicos.edit', compact('serviciosTecnico', 'categorias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServicioTecnico $serviciosTecnico)
    {
        $validated = $request->validate([
            'categoria_id' => 'nullable|exists:categorias_servicios_tecnicos,id',
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'unidad_medida' => 'nullable|string|max:50',
            'precio_base' => 'nullable|numeric|min:0|max:999999.99',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $serviciosTecnico->update($validated);

        Alert::success('Éxito', 'Servicio técnico actualizado correctamente');
        return redirect()->route('admin.servicios-tecnicos.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServicioTecnico $serviciosTecnico)
    {
        try {
            $serviciosTecnico->delete();
            Alert::success('Éxito', 'Servicio técnico eliminado correctamente');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el servicio técnico: ' . $e->getMessage());
        }

        return redirect()->route('admin.servicios-tecnicos.index');
    }

    /**
     * Crear categoría
     */
    public function storeCategoria(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        CategoriaServicioTecnico::create($validated);

        Alert::success('Éxito', 'Categoría creada correctamente');
        return redirect()->route('admin.servicios-tecnicos.index');
    }

    /**
     * Actualizar categoría
     */
    public function updateCategoria(Request $request, CategoriaServicioTecnico $categoria)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'icono' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $categoria->update($validated);

        Alert::success('Éxito', 'Categoría actualizada correctamente');
        return redirect()->route('admin.servicios-tecnicos.index');
    }

    /**
     * Eliminar categoría
     */
    public function destroyCategoria(CategoriaServicioTecnico $categoria)
    {
        try {
            $categoria->delete();
            Alert::success('Éxito', 'Categoría eliminada correctamente');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar la categoría: ' . $e->getMessage());
        }

        return redirect()->route('admin.servicios-tecnicos.index');
    }
}
