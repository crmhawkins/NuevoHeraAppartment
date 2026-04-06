<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Servicio;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AdminServiciosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $servicios = Servicio::orderBy('categoria')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        // Agrupar por categoría para la vista
        $serviciosPorCategoria = $servicios->groupBy('categoria');

        return view('admin.servicios.index', compact('servicios', 'serviciosPorCategoria'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.servicios.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Preparar datos antes de validar - normalizar checkboxes
        $data = $request->all();
        $data['es_popular'] = $request->has('es_popular') && $request->input('es_popular') == '1' ? true : false;
        $data['activo'] = $request->has('activo') && $request->input('activo') == '1' ? true : false;
        
        // Crear un nuevo request con los datos normalizados
        $request->merge($data);

        $validated = $request->validate([
            'icono' => 'nullable|string|max:255',
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:servicios,slug',
            'descripcion' => 'nullable|string',
            'precio' => 'nullable|numeric|min:0|max:999999.99',
            'imagen' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0',
            'categoria' => 'nullable|string|max:255',
            'es_popular' => 'required|boolean',
            'activo' => 'required|boolean',
        ]);

        Servicio::create($validated);

        Alert::success('Éxito', 'Servicio creado correctamente');
        return redirect()->route('admin.servicios.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Servicio $servicio)
    {
        return view('admin.servicios.edit', compact('servicio'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Servicio $servicio)
    {
        try {
            // Preparar datos antes de validar - normalizar checkboxes
            $data = $request->all();
            $data['es_popular'] = $request->has('es_popular') && $request->input('es_popular') == '1' ? true : false;
            $data['activo'] = $request->has('activo') && $request->input('activo') == '1' ? true : false;
            
            // Crear un nuevo request con los datos normalizados
            $request->merge($data);

            $validated = $request->validate([
                'icono' => 'nullable|string|max:255',
                'nombre' => 'required|string|max:255',
                'slug' => 'nullable|string|max:255|unique:servicios,slug,' . $servicio->id,
                'descripcion' => 'nullable|string',
                'precio' => 'nullable|numeric|min:0|max:999999.99',
                'imagen' => 'nullable|string|max:255',
                'orden' => 'nullable|integer|min:0',
                'categoria' => 'nullable|string|max:255',
                'es_popular' => 'required|boolean',
                'activo' => 'required|boolean',
            ], [
                'nombre.required' => 'El nombre del servicio es obligatorio.',
                'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
                'slug.unique' => 'Este slug ya está en uso por otro servicio.',
                'precio.numeric' => 'El precio debe ser un número válido.',
                'precio.min' => 'El precio no puede ser negativo.',
                'precio.max' => 'El precio no puede ser mayor a 999999.99.',
                'orden.integer' => 'El orden debe ser un número entero.',
                'orden.min' => 'El orden no puede ser negativo.',
                'es_popular.boolean' => 'El campo servicio popular debe ser verdadero o falso.',
                'activo.boolean' => 'El campo activo debe ser verdadero o falso.',
            ]);

            $servicio->update($validated);

            Alert::success('Éxito', 'Servicio actualizado correctamente');
            return redirect()->route('admin.servicios.index');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Alert::error('Error', 'Error al actualizar el servicio: ' . $e->getMessage());
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Servicio $servicio)
    {
        $servicio->delete();

        Alert::success('Éxito', 'Servicio eliminado correctamente');
        return redirect()->route('admin.servicios.index');
    }
}
