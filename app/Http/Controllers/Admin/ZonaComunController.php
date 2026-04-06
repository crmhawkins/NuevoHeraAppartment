<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ZonaComun;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ZonaComunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $zonasComunes = ZonaComun::ordenadas()->paginate(20);
        return view('admin.zonas-comunes.index', compact('zonasComunes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.zonas-comunes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ubicacion' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:zona_comun,area_servicio,recepcion,piscina,gimnasio,terraza',
            'orden' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Por favor, corrige los errores en el formulario.');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            ZonaComun::create($request->all());
            Alert::success('Éxito', 'Zona común creada correctamente.');
            return redirect()->route('admin.zonas-comunes.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo crear la zona común.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $zonaComun = ZonaComun::with(['limpiezas'])->findOrFail($id);
        return view('admin.zonas-comunes.show', compact('zonaComun'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $zonaComun = ZonaComun::findOrFail($id);
        return view('admin.zonas-comunes.edit', compact('zonaComun'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $zonaComun = ZonaComun::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'ubicacion' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:zona_comun,area_servicio,recepcion,piscina,gimnasio,terraza',
            'orden' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Por favor, corrige los errores en el formulario.');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $zonaComun->update($request->all());
            Alert::success('Éxito', 'Zona común actualizada correctamente.');
            return redirect()->route('admin.zonas-comunes.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo actualizar la zona común.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $zonaComun = ZonaComun::findOrFail($id);
            
            // Verificar si tiene limpiezas asociadas
            $limpiezasCount = $zonaComun->limpiezas()->count();
            
            if ($limpiezasCount > 0) {
                if (request()->expectsJson()) {
                    $zonaComun->delete(); // Realizar soft delete
                    return response()->json([
                        'success' => true, // Cambiado a true porque la operación fue exitosa
                        'message' => "Esta zona común tiene {$limpiezasCount} limpieza(s) asociada(s). Se eliminó de forma lógica (soft delete) y las limpiezas se mantuvieron."
                    ], 200); // Cambiado a 200 OK
                }
                Alert::warning('Advertencia', "Esta zona común tiene {$limpiezasCount} limpieza(s) asociada(s). Se eliminará de forma lógica (soft delete) y las limpiezas se mantendrán.");
            }
            
            $zonaComun->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Zona común eliminada correctamente.'
                ]);
            }
            
            Alert::success('Éxito', 'Zona común eliminada correctamente.');
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo eliminar la zona común: ' . $e->getMessage()
                ], 500);
            }
            
            Alert::error('Error', 'No se pudo eliminar la zona común: ' . $e->getMessage());
        }

        return redirect()->route('admin.zonas-comunes.index');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(string $id)
    {
        try {
            $zonaComun = ZonaComun::findOrFail($id);
            $zonaComun->update(['activo' => !$zonaComun->activo]);
            
            $status = $zonaComun->activo ? 'activada' : 'desactivada';
            Alert::success('Éxito', "Zona común {$status} correctamente.");
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado de la zona común.');
        }

        return redirect()->route('admin.zonas-comunes.index');
    }
}
