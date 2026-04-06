<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reparaciones;
use App\Models\ServicioTecnico;
use App\Models\CategoriaServicioTecnico;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Facades\DB;

class TecnicosServiciosController extends Controller
{
    /**
     * Mostrar listado de técnicos
     */
    public function index()
    {
        $tecnicos = Reparaciones::withCount('servicios')
            ->orderBy('nombre')
            ->get();

        return view('admin.tecnicos-servicios.index', compact('tecnicos'));
    }

    /**
     * Mostrar formulario para asignar servicios a un técnico
     */
    public function show($tecnicoId)
    {
        $tecnico = Reparaciones::findOrFail($tecnicoId);
        
        // Obtener servicios con sus precios asignados al técnico
        $servicios = ServicioTecnico::activos()
            ->ordenados()
            ->with(['categoria'])
            ->get()
            ->map(function ($servicio) use ($tecnico) {
                $pivot = DB::table('tecnico_servicio_precio')
                    ->where('tecnico_id', $tecnico->id)
                    ->where('servicio_id', $servicio->id)
                    ->first();
                
                $servicio->precio_asignado = $pivot ? $pivot->precio : null;
                $servicio->observaciones_asignadas = $pivot ? $pivot->observaciones : null;
                $servicio->activo_asignado = $pivot ? $pivot->activo : false;
                $servicio->tiene_precio = $pivot !== null;
                
                return $servicio;
            });

        // Agrupar por categoría
        $categorias = CategoriaServicioTecnico::activas()
            ->ordenadas()
            ->get();

        $serviciosPorCategoria = $servicios->groupBy('categoria_id');

        return view('admin.tecnicos-servicios.show', compact('tecnico', 'servicios', 'categorias', 'serviciosPorCategoria'));
    }

    /**
     * Guardar/Actualizar precios de servicios para un técnico
     */
    public function store(Request $request, $tecnicoId)
    {
        $tecnico = Reparaciones::findOrFail($tecnicoId);

        $request->validate([
            'servicios' => 'required|array',
            'servicios.*.servicio_id' => 'required|exists:servicios_tecnicos,id',
            'servicios.*.precio' => 'required|numeric|min:0|max:999999.99',
            'servicios.*.observaciones' => 'nullable|string',
            'servicios.*.activo' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->servicios as $servicioData) {
                $servicioId = $servicioData['servicio_id'];
                $precio = $servicioData['precio'];
                $observaciones = $servicioData['observaciones'] ?? null;
                $activo = isset($servicioData['activo']) ? (bool)$servicioData['activo'] : true;

                // Usar updateOrInsert para crear o actualizar
                DB::table('tecnico_servicio_precio')
                    ->updateOrInsert(
                        [
                            'tecnico_id' => $tecnico->id,
                            'servicio_id' => $servicioId
                        ],
                        [
                            'precio' => $precio,
                            'observaciones' => $observaciones,
                            'activo' => $activo,
                            'updated_at' => now(),
                            'created_at' => DB::raw('COALESCE(created_at, NOW())')
                        ]
                    );
            }

            DB::commit();
            Alert::success('Éxito', 'Precios de servicios actualizados correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            Alert::error('Error', 'No se pudieron actualizar los precios: ' . $e->getMessage());
        }

        return redirect()->route('admin.tecnicos-servicios.show', $tecnico->id);
    }

    /**
     * Eliminar precio de un servicio para un técnico
     */
    public function destroy($tecnicoId, $servicioId)
    {
        $tecnico = Reparaciones::findOrFail($tecnicoId);
        $servicio = ServicioTecnico::findOrFail($servicioId);

        try {
            DB::table('tecnico_servicio_precio')
                ->where('tecnico_id', $tecnico->id)
                ->where('servicio_id', $servicio->id)
                ->delete();

            Alert::success('Éxito', 'Precio eliminado correctamente');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el precio: ' . $e->getMessage());
        }

        return redirect()->route('admin.tecnicos-servicios.show', $tecnico->id);
    }
}
