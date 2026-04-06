<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemChecklist;
use App\Models\Checklist;
use Illuminate\Validation\Rule;

class ItemChecklistController extends Controller
{
    public function index(Request $request)
    {
        if (!isset($request->id)) {
            return redirect()->route('admin.checklists.index')
                ->with('swal_error', 'Debes seleccionar un checklist para ver sus items.');
        }

        $checklist = Checklist::with(['edificio'])->findOrFail($request->id);
        $search = $request->get('search');
        $sort = $request->get('sort', 'id');
        $order = $request->get('order', 'asc');

        $items = ItemChecklist::with('articulo')
            ->where('checklist_id', $request->id)
            ->when($search, function ($query, $search) {
                $query->where('nombre', 'like', '%' . $search . '%');
            })
            ->orderBy($sort, $order)
            ->paginate(20);

        return view('admin.itemsChecklist.index', compact('checklist', 'items', 'search', 'sort', 'order'));
    }

    public function create(Request $request)
    {
        if (!isset($request->id)) {
            return redirect()->route('admin.checklists.index')
                ->with('swal_error', 'Debes seleccionar un checklist para crear items.');
        }

        $checklist = Checklist::with(['edificio'])->findOrFail($request->id);
        return view('admin.itemsChecklist.create', compact('checklist'));
    }

    public function store(Request $request)
    {
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'nullable|string|in:simple,multiple,texto,foto',
            'obligatorio' => 'boolean',
            'orden' => 'nullable|integer|min:1',
            'checklistId' => 'required|exists:checklists,id',
            'tiene_stock' => 'boolean',
            'articulo_id' => 'nullable|exists:articulos,id',
            'cantidad_requerida' => 'nullable|numeric|min:0',
            'tiene_averias' => 'boolean',
            'observaciones_stock' => 'nullable|string|max:500'
        ];

        $messages = [
            'nombre.required' => 'El nombre del item es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser mayor a 0.',
            'checklistId.required' => 'Debes seleccionar un checklist.',
            'checklistId.exists' => 'El checklist seleccionado no existe.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['obligatorio'] = $request->has('obligatorio');
            $validatedData['tiene_stock'] = $request->has('tiene_stock');
            $validatedData['tiene_averias'] = $request->has('tiene_averias');
            $validatedData['tipo'] = $validatedData['tipo'] ?? 'simple';
            $validatedData['orden'] = $validatedData['orden'] ?? 1;

            $item = ItemChecklist::create([
                'nombre' => $validatedData['nombre'],
                'descripcion' => $validatedData['descripcion'],
                'tipo' => $validatedData['tipo'],
                'obligatorio' => $validatedData['obligatorio'],
                'orden' => $validatedData['orden'],
                'checklist_id' => $validatedData['checklistId'],
                'tiene_stock' => $validatedData['tiene_stock'],
                'articulo_id' => $validatedData['articulo_id'] ?? null,
                'cantidad_requerida' => $validatedData['cantidad_requerida'] ?? null,
                'tiene_averias' => $validatedData['tiene_averias'],
                'observaciones_stock' => $validatedData['observaciones_stock'] ?? null
            ]);

            return redirect()->route('admin.itemsChecklist.index', ['id' => $validatedData['checklistId']])
                ->with('swal_success', '¡Item creado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el item: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $item = ItemChecklist::with(['checklist.edificio'])->findOrFail($id);
        
        // Estadísticas del item
        $totalControles = $item->controles->count();
        $controlesCompletados = $item->controles->where('completado', true)->count();
        $porcentajeCompletado = $totalControles > 0 ? round(($controlesCompletados / $totalControles) * 100, 1) : 0;
        
        return view('admin.itemsChecklist.show', compact(
            'item', 
            'totalControles', 
            'controlesCompletados',
            'porcentajeCompletado'
        ));
    }

    public function edit($id)
    {
        $item = ItemChecklist::with(['checklist'])->findOrFail($id);
        $checklist = $item->checklist;
        
        return view('admin.itemsChecklist.edit', compact('checklist', 'item'));
    }

    public function update(Request $request, $id)
    {
        $item = ItemChecklist::findOrFail($id);
        
        $rules = [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'tipo' => 'nullable|string|in:simple,multiple,texto,foto',
            'obligatorio' => 'boolean',
            'orden' => 'nullable|integer|min:1',
            'tiene_stock' => 'boolean',
            'articulo_id' => 'nullable|exists:articulos,id',
            'cantidad_requerida' => 'nullable|numeric|min:0',
            'tiene_averias' => 'boolean',
            'observaciones_stock' => 'nullable|string|max:500'
        ];

        $messages = [
            'nombre.required' => 'El nombre del item es obligatorio.',
            'nombre.max' => 'El nombre no puede tener más de 255 caracteres.',
            'descripcion.max' => 'La descripción no puede tener más de 1000 caracteres.',
            'tipo.in' => 'El tipo seleccionado no es válido.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser mayor a 0.'
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            
            // Establecer valores por defecto
            $validatedData['obligatorio'] = $request->has('obligatorio');
            $validatedData['tiene_stock'] = $request->has('tiene_stock');
            $validatedData['tiene_averias'] = $request->has('tiene_averias');
            $validatedData['tipo'] = $validatedData['tipo'] ?? 'simple';
            $validatedData['orden'] = $validatedData['orden'] ?? 1;

            $item->update($validatedData);

            return redirect()->route('admin.itemsChecklist.index', ['id' => $item->checklist_id])
                ->with('swal_success', '¡Item actualizado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el item: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            $item = ItemChecklist::with(['controles'])->findOrFail($id);
            $checklistId = $item->checklist_id;
            
            // Verificar si tiene controles asociados
            if ($item->controles->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el item porque tiene controles asociados.');
            }

            $item->delete();

            return redirect()->route('admin.itemsChecklist.index', ['id' => $checklistId])
                ->with('swal_success', '¡Item eliminado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el item: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        try {
            $item = ItemChecklist::findOrFail($id);
            $item->activo = !$item->activo;
            $item->save();

            $status = $item->activo ? 'activado' : 'desactivado';
            return redirect()->back()
                ->with('swal_success', "¡Item {$status} con éxito!");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al cambiar el estado del item: ' . $e->getMessage());
        }
    }

    public function reorder(Request $request)
    {
        try {
            $request->validate([
                'items' => 'required|array',
                'items.*.id' => 'required|exists:items_checklists,id',
                'items.*.orden' => 'required|integer|min:1'
            ]);

            foreach ($request->items as $itemData) {
                ItemChecklist::where('id', $itemData['id'])
                    ->update(['orden' => $itemData['orden']]);
            }

            return response()->json(['success' => true, 'message' => 'Orden actualizado correctamente']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al actualizar el orden: ' . $e->getMessage()], 500);
        }
    }
}
