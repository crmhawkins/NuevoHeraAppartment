<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistZonaComun;
use App\Models\ItemChecklistZonaComun;
use Illuminate\Support\Facades\Validator;
use RealRashid\SweetAlert\Facades\Alert;

class ChecklistZonaComunController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $checklists = ChecklistZonaComun::with(['items'])->ordenados()->paginate(20);
        return view('admin.checklists-zonas-comunes.index', compact('checklists'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.checklists-zonas-comunes.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string|in:general,recepcion,piscina,gimnasio,terraza,area_servicio',
            'orden' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Por favor, corrige los errores en el formulario.');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            ChecklistZonaComun::create($request->all());
            Alert::success('Éxito', 'Checklist de zona común creado correctamente.');
            return redirect()->route('admin.checklists-zonas-comunes.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo crear el checklist.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $checklist = ChecklistZonaComun::with(['items'])->findOrFail($id);
        return view('admin.checklists-zonas-comunes.show', compact('checklist'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $checklist = ChecklistZonaComun::with(['items'])->findOrFail($id);
        return view('admin.checklists-zonas-comunes.edit', compact('checklist'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $checklist = ChecklistZonaComun::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'required|string|in:general,recepcion,piscina,gimnasio,terraza,area_servicio',
            'orden' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Por favor, corrige los errores en el formulario.');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $checklist->update($request->all());
            Alert::success('Éxito', 'Checklist actualizado correctamente.');
            return redirect()->route('admin.checklists-zonas-comunes.index');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo actualizar el checklist.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $checklist = ChecklistZonaComun::findOrFail($id);
            $checklist->delete();
            Alert::success('Éxito', 'Checklist eliminado correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo eliminar el checklist.');
        }

        return redirect()->route('admin.checklists-zonas-comunes.index');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(string $id)
    {
        try {
            $checklist = ChecklistZonaComun::findOrFail($id);
            $checklist->update(['activo' => !$checklist->activo]);
            
            $status = $checklist->activo ? 'activado' : 'desactivado';
            Alert::success('Éxito', "Checklist {$status} correctamente.");
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo cambiar el estado del checklist.');
        }

        return redirect()->route('admin.checklists-zonas-comunes.index');
    }

    /**
     * Manage items for a checklist
     */
    public function manageItems(string $id)
    {
        $checklist = ChecklistZonaComun::with(['items'])->findOrFail($id);
        return view('admin.checklists-zonas-comunes.items', compact('checklist'));
    }

    /**
     * Store item for checklist
     */
    public function storeItem(Request $request, string $id)
    {
        $checklist = ChecklistZonaComun::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:255',
            'orden' => 'nullable|integer|min:0'
        ]);

        if ($validator->fails()) {
            Alert::error('Error', 'Por favor, corrige los errores en el formulario.');
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $checklist->items()->create($request->all());
            Alert::success('Éxito', 'Item añadido correctamente.');
        } catch (\Exception $e) {
            Alert::error('Error', 'No se pudo añadir el item.');
        }

        return redirect()->route('admin.checklists-zonas-comunes.items', $id);
    }
}
