<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class TarifaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tarifas = Tarifa::with('apartamentos')->orderBy('created_at', 'desc')->paginate(10);
        return view('admin.tarifas.index', compact('tarifas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $apartamentos = Apartamento::with('edificioName')->get();
        
        // Verificar que las relaciones se cargaron correctamente
        $apartamentos->each(function ($apartamento) {
            if (!$apartamento->edificioName) {
                Log::warning("Apartamento ID {$apartamento->id} no tiene edificio asociado");
            }
        });
        
        return view('admin.tarifas.create', compact('apartamentos'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'temporada_alta' => 'boolean',
            'temporada_baja' => 'boolean',
            'apartamentos' => 'array',
            'apartamentos.*' => 'exists:apartamentos,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $tarifa = Tarifa::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'temporada_alta' => $request->has('temporada_alta'),
            'temporada_baja' => $request->has('temporada_baja'),
            'activo' => true
        ]);

        // Asignar apartamentos si se seleccionaron
        if ($request->has('apartamentos')) {
            $apartamentos = collect($request->apartamentos)->mapWithKeys(function ($apartamentoId) {
                return [$apartamentoId => ['activo' => true]];
            });
            $tarifa->apartamentos()->attach($apartamentos);
        }

        return redirect()->route('tarifas.index')
            ->with('success', 'Tarifa creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Tarifa $tarifa)
    {
        $tarifa->load(['apartamentos' => function($query) {
            $query->with('edificioName');
        }]);
        return view('admin.tarifas.show', compact('tarifa'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tarifa $tarifa)
    {
        $apartamentos = Apartamento::with('edificioName')->get();
        
        // Verificar que las relaciones se cargaron correctamente
        $apartamentos->each(function ($apartamento) {
            if (!$apartamento->edificioName) {
                Log::warning("Apartamento ID {$apartamento->id} no tiene edificio asociado");
            }
        });
        
        $tarifa->load('apartamentos');
        return view('admin.tarifas.edit', compact('tarifa', 'apartamentos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tarifa $tarifa)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'precio' => 'required|numeric|min:0',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'temporada_alta' => 'boolean',
            'temporada_baja' => 'boolean',
            'apartamentos' => 'array',
            'apartamentos.*' => 'exists:apartamentos,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $tarifa->update([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'precio' => $request->precio,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
            'temporada_alta' => $request->has('temporada_alta'),
            'temporada_baja' => $request->has('temporada_baja')
        ]);

        // Actualizar apartamentos asignados
        $apartamentos = collect($request->apartamentos ?? [])->mapWithKeys(function ($apartamentoId) {
            return [$apartamentoId => ['activo' => true]];
        });
        $tarifa->apartamentos()->sync($apartamentos);

        return redirect()->route('tarifas.index')
            ->with('success', 'Tarifa actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tarifa $tarifa)
    {
        $tarifa->apartamentos()->detach();
        $tarifa->delete();

        return redirect()->route('tarifas.index')
            ->with('success', 'Tarifa eliminada exitosamente.');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(Tarifa $tarifa)
    {
        $tarifa->update(['activo' => !$tarifa->activo]);
        
        return redirect()->route('tarifas.index')
            ->with('success', 'Estado de la tarifa actualizado.');
    }

    /**
     * Asignar tarifa a apartamento
     */
    public function asignarApartamento(Request $request, Tarifa $tarifa)
    {
        $validator = Validator::make($request->all(), [
            'apartamento_id' => 'required|exists:apartamentos,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Apartamento no válido']);
        }

        $tarifa->apartamentos()->attach($request->apartamento_id, ['activo' => true]);

        return response()->json(['success' => true, 'message' => 'Tarifa asignada al apartamento']);
    }

    /**
     * Desasignar tarifa de apartamento
     */
    public function desasignarApartamento(Request $request, Tarifa $tarifa)
    {
        $validator = Validator::make($request->all(), [
            'apartamento_id' => 'required|exists:apartamentos,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Apartamento no válido']);
        }

        $tarifa->apartamentos()->detach($request->apartamento_id);

        return response()->json(['success' => true, 'message' => 'Tarifa desasignada del apartamento']);
    }
}
