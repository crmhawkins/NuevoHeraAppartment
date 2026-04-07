<?php

namespace App\Http\Controllers;

use App\Models\Edificio;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Html\Editor\Editor;
use App\Services\MetodoEntradaService;

class EdificiosController extends Controller
{
    public function index(Request $request) {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id'); // Default sort column
        $order = $request->get('order', 'asc'); // Default sort order

        $edificios = Edificio::with(['apartamentos', 'checklists'])
            ->where(function ($query) use ($search) {
                $query->where('nombre', 'like', '%'.$search.'%')
                      ->orWhere('clave', 'like', '%'.$search.'%');
            })
            ->orderBy($sort, $order)
            ->paginate(20);

        return view('admin.edificios.index', compact('edificios', 'search', 'sort', 'order'));
    }

    public function create(){
        return view('admin.edificios.create');
    }

    public function store(Request $request){
        // El option "por defecto" llega como string vacío (""), normalizamos a null.
        $request->merge([
            'metodo_entrada' => $request->filled('metodo_entrada') ? $request->input('metodo_entrada') : null,
        ]);

        $rules = [
            'nombre' => 'required|string|max:255|unique:edificios,nombre',
            'clave' => 'required|string|max:255|unique:edificios,clave',
            'codigo_establecimiento' => 'nullable|string|max:50',
            'metodo_entrada' => 'nullable|string|in:' . MetodoEntradaService::METODO_FISICA . ',' . MetodoEntradaService::METODO_DIGITAL,
            'tipo_cerradura_principal' => 'nullable|string|in:manual,ttlock,tuya',
            'tuyalaravel_building_id' => 'nullable|integer',
        ];

        $messages = [
            'nombre.required' => 'El nombre del edificio es obligatorio.',
            'nombre.unique' => 'Ya existe un edificio con ese nombre.',
            'clave.required' => 'La clave de acceso es obligatoria.',
            'clave.unique' => 'Ya existe un edificio con esa clave.',
        ];

        try {
            $validatedData = $request->validate($rules, $messages);
            $edificio = Edificio::create([
                'nombre' => $validatedData['nombre'],
                'clave' => $validatedData['clave'],
                'codigo_establecimiento' => $validatedData['codigo_establecimiento'] ?? null,
                'metodo_entrada' => $validatedData['metodo_entrada'] ?? null,
                'mir_activo' => $request->has('mir_activo') ? (bool) $request->input('mir_activo') : true,
                'tipo_cerradura_principal' => $validatedData['tipo_cerradura_principal'] ?? 'manual',
                'tuyalaravel_building_id' => $validatedData['tuyalaravel_building_id'] ?? null,
            ]);

            return redirect()->route('admin.edificios.index')
                ->with('swal_success', '¡Edificio creado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al crear el edificio: ' . $e->getMessage());
        }
    }

    public function edit($id){
        $edificio = Edificio::findOrFail($id);
        return view('admin.edificios.edit', compact('edificio'));
    }

    public function update(Request $request, $id){
        $edificio = Edificio::findOrFail($id);

        // El option "por defecto" llega como string vacío (""), normalizamos a null.
        $request->merge([
            'metodo_entrada' => $request->filled('metodo_entrada') ? $request->input('metodo_entrada') : null,
        ]);
        
        $rules = [
            'nombre' => [
                'required',
                'string',
                'max:255',
                Rule::unique('edificios')->ignore($edificio->id)
            ],
            'clave' => [
                'required',
                'string',
                'max:255',
                Rule::unique('edificios')->ignore($edificio->id)
            ],
            'codigo_establecimiento' => [
                'nullable',
                'string',
                'max:50',
            ],
            'metodo_entrada' => [
                'nullable',
                'string',
                'in:' . MetodoEntradaService::METODO_FISICA . ',' . MetodoEntradaService::METODO_DIGITAL,
            ],
            'tipo_cerradura_principal' => [
                'nullable',
                'string',
                'in:manual,ttlock,tuya',
            ],
            'tuyalaravel_building_id' => [
                'nullable',
                'integer',
            ],
        ];

        $messages = [
            'nombre.required' => 'El nombre del edificio es obligatorio.',
            'nombre.unique' => 'Ya existe un edificio con ese nombre.',
            'clave.required' => 'La clave de acceso es obligatoria.',
            'clave.unique' => 'Ya existe un edificio con esa clave.',
        ];

        try {
            $validatedData = $request->validate($rules, $messages);

            $edificio->update([
                'nombre' => $validatedData['nombre'],
                'clave' => $validatedData['clave'],
                'codigo_establecimiento' => $validatedData['codigo_establecimiento'] ?? null,
                'metodo_entrada' => $validatedData['metodo_entrada'] ?? null,
                'mir_activo' => (bool) $request->input('mir_activo', 0),
                'tipo_cerradura_principal' => $validatedData['tipo_cerradura_principal'] ?? 'manual',
                'tuyalaravel_building_id' => $validatedData['tuyalaravel_building_id'] ?? null,
            ]);

            return redirect()->route('admin.edificios.index')
                ->with('swal_success', '¡Edificio actualizado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('swal_error', 'Error al actualizar el edificio: ' . $e->getMessage());
        }
    }

    public function destroy($id){
        try {
            $edificio = Edificio::findOrFail($id);
            
            // Verificar si tiene apartamentos asociados
            if ($edificio->apartamentos()->count() > 0) {
                return redirect()->back()
                    ->with('swal_error', 'No se puede eliminar el edificio porque tiene apartamentos asociados.');
            }

            $edificio->delete();
            return redirect()->route('admin.edificios.index')
                ->with('swal_success', '¡Edificio eliminado con éxito!');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('swal_error', 'Error al eliminar el edificio: ' . $e->getMessage());
        }
    }

    public function show($id) {
        $edificio = Edificio::with(['apartamentos', 'checklists'])
            ->findOrFail($id);
        
        // Estadísticas del edificio
        $totalApartamentos = $edificio->apartamentos->count();
        $totalChecklists = $edificio->checklists->count();
        $apartamentosActivos = $edificio->apartamentos->where('estado', 'activo')->count();
        
        // Apartamentos por estado
        $apartamentosPorEstado = $edificio->apartamentos
            ->groupBy('estado')
            ->map(function ($apartamentos) {
                return $apartamentos->count();
            })
            ->toArray();

        return view('admin.edificios.show', compact(
            'edificio', 
            'totalApartamentos', 
            'totalChecklists', 
            'apartamentosActivos',
            'apartamentosPorEstado'
        ));
    }
}
