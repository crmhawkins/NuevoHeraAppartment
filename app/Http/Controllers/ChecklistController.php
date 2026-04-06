<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Checklist;
use App\Models\ChecklistPhotoRequirement;
use App\Models\Edificio;
use Illuminate\Validation\Rule;

class ChecklistController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');
        $edificioId = $request->get('edificio_id');

        $checklists = Checklist::with(['edificio', 'items', 'photoRequirements'])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('nombre', 'like', '%' . $search . '%')
                      ->orWhereHas('edificio', function ($q2) use ($search) {
                          $q2->where('nombre', 'like', '%' . $search . '%');
                      });
                });
            })
            ->when($edificioId, function ($query, $edificioId) {
                $query->where('edificio_id', $edificioId);
            })
            ->orderBy($sort, $order)
            ->paginate(20);

        $edificios = Edificio::all();

        return view('admin.checklists.index', compact('checklists', 'edificios', 'search', 'sort', 'order'));
    }

    public function create()
    {
        $edificios = Edificio::orderBy('nombre')->get();
        return view('admin.checklists.create', compact('edificios'));
    }

    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'edificio_id' => 'required|exists:edificios,id',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'nullable|string|in:limpieza,mantenimiento,inspeccion,seguridad',
            'activo' => 'boolean'
        ];

        $messages = [
            'nombre.required' => 'El nombre del checklist es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'edificio_id.required' => 'Debes seleccionar un edificio.',
            'edificio_id.exists' => 'El edificio seleccionado no existe.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'tipo.in' => 'El tipo seleccionado no es válido.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['activo'] = $request->has('activo');
            $validatedData['tipo'] = $validatedData['tipo'] ?? 'limpieza';

            $checklist = Checklist::create($validatedData);

            // Guardar requisitos de fotos si existen
            if ($request->has('photo_names') && is_array($request->photo_names)) {
                foreach ($request->photo_names as $index => $name) {
                    if (!empty($name)) {
                        ChecklistPhotoRequirement::create([
                            'checklist_id' => $checklist->id,
                            'nombre' => $name,
                            'descripcion' => $request->photo_descriptions[$index] ?? null,
                            'cantidad' => $request->photo_quantities[$index] ?? 1,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.checklists.index')
                ->with('swal_success', '¡Checklist creado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el checklist: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $checklist = Checklist::with(['edificio', 'items', 'photoRequirements'])
            ->findOrFail($id);
        
        // Estadísticas del checklist
        $totalItems = $checklist->items->count();
        $totalPhotoRequirements = $checklist->photoRequirements->count();
        $itemsCompletados = $checklist->items->where('completado', true)->count();
        $porcentajeCompletado = $totalItems > 0 ? round(($itemsCompletados / $totalItems) * 100, 1) : 0;
        
        return view('admin.checklists.show', compact(
            'checklist', 
            'totalItems', 
            'totalPhotoRequirements', 
            'itemsCompletados',
            'porcentajeCompletado'
        ));
    }

    public function edit($id)
    {
        $checklist = Checklist::with(['photoRequirements'])->findOrFail($id);
        $edificios = Edificio::orderBy('nombre')->get();

        return view('admin.checklists.edit', compact('checklist', 'edificios'));
    }

    public function update(Request $request, $id)
    {
        $checklist = Checklist::findOrFail($id);
        
        $rules = [
            'nombre' => 'required|string|max:255',
            'edificio_id' => 'required|exists:edificios,id',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'nullable|string|in:limpieza,mantenimiento,inspeccion,seguridad',
            'activo' => 'boolean'
        ];

        $messages = [
            'nombre.required' => 'El nombre del checklist es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'edificio_id.required' => 'Debes seleccionar un edificio.',
            'edificio_id.exists' => 'El edificio seleccionado no existe.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'tipo.in' => 'El tipo seleccionado no es válido.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['activo'] = $request->has('activo');
            $validatedData['tipo'] = $validatedData['tipo'] ?? 'limpieza';

            $checklist->update($validatedData);

            // Actualizar requisitos de fotos
            if ($request->has('photo_names') && is_array($request->photo_names)) {
                // Eliminar requisitos existentes
                ChecklistPhotoRequirement::where('checklist_id', $checklist->id)->delete();
                
                // Crear nuevos requisitos
                foreach ($request->photo_names as $index => $name) {
                    if (!empty($name)) {
                        ChecklistPhotoRequirement::create([
                            'checklist_id' => $checklist->id,
                            'nombre' => $name,
                            'descripcion' => $request->photo_descriptions[$index] ?? null,
                            'cantidad' => $request->photo_quantities[$index] ?? 1,
                        ]);
                    }
                }
            }

            return redirect()->route('admin.checklists.index')
                ->with('swal_success', '¡Checklist actualizado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el checklist: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $checklist = Checklist::with(['items'])->findOrFail($id);
            
            // Eliminar primero todos los items asociados
            if ($checklist->items->count() > 0) {
                foreach ($checklist->items as $item) {
                    $item->delete();
                }
            }

            // Luego eliminar el checklist
            $checklist->delete();
            
            return redirect()->route('admin.checklists.index')
                ->with('swal_success', '¡Checklist y sus items eliminados con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el checklist: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $checklist = Checklist::findOrFail($id);
            $checklist->activo = !$checklist->activo;
            $checklist->save();

            $status = $checklist->activo ? 'activado' : 'desactivado';
            return redirect()->back()
                ->with('swal_success', "¡Checklist {$status} con éxito!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al cambiar el estado del checklist: ' . $e->getMessage());
        }
    }
}
