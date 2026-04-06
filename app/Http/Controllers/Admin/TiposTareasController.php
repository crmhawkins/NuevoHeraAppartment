<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TipoTarea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TiposTareasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->get('search');
        $categoria = $request->get('categoria');
        $sort = $request->get('sort', 'nombre');
        $order = $request->get('order', 'asc');

        $query = TipoTarea::query();

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', '%' . $search . '%')
                  ->orWhere('descripcion', 'like', '%' . $search . '%');
            });
        }

        if ($categoria) {
            $query->where('categoria', $categoria);
        }

        $tiposTareas = $query->orderBy($sort, $order)->paginate(20);

        $categorias = [
            'limpieza_apartamento' => 'Limpieza de Apartamento',
            'limpieza_zona_comun' => 'Limpieza de Zona Común',
            'limpieza_oficina' => 'Limpieza de Oficina',
            'preparacion_amenities' => 'Preparación de Amenities',
            'planchado' => 'Planchado',
            'mantenimiento' => 'Mantenimiento',
            'otro' => 'Otro'
        ];

        return view('admin.tipos-tareas.index', compact(
            'tiposTareas', 
            'categorias', 
            'search', 
            'categoria', 
            'sort', 
            'order'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = [
            'limpieza_apartamento' => 'Limpieza de Apartamento',
            'limpieza_zona_comun' => 'Limpieza de Zona Común',
            'limpieza_oficina' => 'Limpieza de Oficina',
            'preparacion_amenities' => 'Preparación de Amenities',
            'planchado' => 'Planchado',
            'mantenimiento' => 'Mantenimiento',
            'otro' => 'Otro'
        ];

        return view('admin.tipos-tareas.create', compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|in:limpieza_apartamento,limpieza_zona_comun,limpieza_oficina,preparacion_amenities,planchado,mantenimiento,otro',
            'prioridad_base' => 'required|integer|min:1|max:10',
            'tiempo_estimado_minutos' => 'required|integer|min:1',
            'dias_max_sin_limpiar' => 'nullable|integer|min:1',
            'incremento_prioridad_por_dia' => 'required|integer|min:0',
            'prioridad_maxima' => 'required|integer|min:1|max:10',
            'requiere_apartamento' => 'nullable|boolean',
            'requiere_zona_comun' => 'nullable|boolean',
            'instrucciones' => 'nullable|string|max:2000'
        ], [
            'nombre.required' => 'El nombre de la tarea es obligatorio',
            'categoria.required' => 'Debe seleccionar una categoría',
            'prioridad_base.required' => 'La prioridad base es obligatoria',
            'tiempo_estimado_minutos.required' => 'El tiempo estimado es obligatorio',
            'incremento_prioridad_por_dia.required' => 'El incremento de prioridad es obligatorio',
            'prioridad_maxima.required' => 'La prioridad máxima es obligatoria'
        ]);

        try {
            TipoTarea::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'prioridad_base' => $request->prioridad_base,
                'tiempo_estimado_minutos' => $request->tiempo_estimado_minutos,
                'dias_max_sin_limpiar' => $request->dias_max_sin_limpiar,
                'incremento_prioridad_por_dia' => $request->incremento_prioridad_por_dia,
                'prioridad_maxima' => $request->prioridad_maxima,
                'requiere_apartamento' => $request->boolean('requiere_apartamento'),
                'requiere_zona_comun' => $request->boolean('requiere_zona_comun'),
                'instrucciones' => $request->instrucciones,
                'activo' => true
            ]);

            return redirect()->route('admin.tipos-tareas.index')
                ->with('success', 'Tipo de tarea creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creando tipo de tarea: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el tipo de tarea: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoTarea $tiposTarea)
    {
        $tiposTarea->load(['tareasAsignadas.turno.user']);
        
        return view('admin.tipos-tareas.show', compact('tiposTarea'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoTarea $tiposTarea)
    {
        $categorias = [
            'limpieza_apartamento' => 'Limpieza de Apartamento',
            'limpieza_zona_comun' => 'Limpieza de Zona Común',
            'limpieza_oficina' => 'Limpieza de Oficina',
            'preparacion_amenities' => 'Preparación de Amenities',
            'planchado' => 'Planchado',
            'mantenimiento' => 'Mantenimiento',
            'otro' => 'Otro'
        ];

        return view('admin.tipos-tareas.edit', compact('tiposTarea', 'categorias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoTarea $tiposTarea)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'categoria' => 'required|in:limpieza_apartamento,limpieza_zona_comun,limpieza_oficina,preparacion_amenities,planchado,mantenimiento,otro',
            'prioridad_base' => 'required|integer|min:1|max:10',
            'tiempo_estimado_minutos' => 'required|integer|min:1',
            'dias_max_sin_limpiar' => 'nullable|integer|min:1',
            'incremento_prioridad_por_dia' => 'required|integer|min:0',
            'prioridad_maxima' => 'required|integer|min:1|max:10',
            'requiere_apartamento' => 'nullable|boolean',
            'requiere_zona_comun' => 'nullable|boolean',
            'activo' => 'boolean',
            'instrucciones' => 'nullable|string|max:2000'
        ]);

        try {
            $tiposTarea->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'prioridad_base' => $request->prioridad_base,
                'tiempo_estimado_minutos' => $request->tiempo_estimado_minutos,
                'dias_max_sin_limpiar' => $request->dias_max_sin_limpiar,
                'incremento_prioridad_por_dia' => $request->incremento_prioridad_por_dia,
                'prioridad_maxima' => $request->prioridad_maxima,
                'requiere_apartamento' => $request->boolean('requiere_apartamento'),
                'requiere_zona_comun' => $request->boolean('requiere_zona_comun'),
                'activo' => $request->boolean('activo'),
                'instrucciones' => $request->instrucciones
            ]);

            return redirect()->route('admin.tipos-tareas.index')
                ->with('success', 'Tipo de tarea actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error actualizando tipo de tarea: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al actualizar el tipo de tarea: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoTarea $tiposTarea)
    {
        try {
            // Verificar si tiene tareas asignadas
            if ($tiposTarea->tareasAsignadas()->exists()) {
                return redirect()->back()
                    ->with('error', 'No se puede eliminar este tipo de tarea porque tiene tareas asignadas');
            }

            $tiposTarea->delete();

            return redirect()->route('admin.tipos-tareas.index')
                ->with('success', 'Tipo de tarea eliminado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error eliminando tipo de tarea: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al eliminar el tipo de tarea: ' . $e->getMessage());
        }
    }

    /**
     * Toggle active status
     */
    public function toggleActive(TipoTarea $tiposTarea)
    {
        try {
            $tiposTarea->update(['activo' => !$tiposTarea->activo]);
            
            $status = $tiposTarea->activo ? 'activado' : 'desactivado';
            
            return response()->json([
                'success' => true,
                'message' => "Tipo de tarea {$status} exitosamente",
                'activo' => $tiposTarea->activo
            ]);

        } catch (\Exception $e) {
            Log::error('Error cambiando estado del tipo de tarea: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicar tipo de tarea
     */
    public function duplicar(TipoTarea $tiposTarea)
    {
        try {
            $nuevoTipo = $tiposTarea->replicate();
            $nuevoTipo->nombre = $tiposTarea->nombre . ' (Copia)';
            $nuevoTipo->save();

            return redirect()->route('admin.tipos-tareas.edit', $nuevoTipo)
                ->with('success', 'Tipo de tarea duplicado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error duplicando tipo de tarea: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al duplicar el tipo de tarea: ' . $e->getMessage());
        }
    }
}