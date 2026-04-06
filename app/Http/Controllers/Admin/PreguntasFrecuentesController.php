<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreguntaFrecuente;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PreguntasFrecuentesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $preguntas = PreguntaFrecuente::ordenadas()->get();
        $categorias = PreguntaFrecuente::categorias();
        return view('admin.preguntas-frecuentes.index', compact('preguntas', 'categorias'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categorias = PreguntaFrecuente::categorias();
        return view('admin.preguntas-frecuentes.create', compact('categorias'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'categoria' => 'nullable|string|max:100',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        PreguntaFrecuente::create($validated);

        Alert::success('Éxito', 'Pregunta frecuente creada correctamente');
        return redirect()->route('admin.preguntas-frecuentes.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pregunta = PreguntaFrecuente::findOrFail($id);
        return view('admin.preguntas-frecuentes.show', compact('pregunta'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $pregunta = PreguntaFrecuente::findOrFail($id);
        $categorias = PreguntaFrecuente::categorias();
        return view('admin.preguntas-frecuentes.edit', compact('pregunta', 'categorias'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $pregunta = PreguntaFrecuente::findOrFail($id);
        
        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'categoria' => 'nullable|string|max:100',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $pregunta->update($validated);

        Alert::success('Éxito', 'Pregunta frecuente actualizada correctamente');
        return redirect()->route('admin.preguntas-frecuentes.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pregunta = PreguntaFrecuente::findOrFail($id);
        $pregunta->delete();

        Alert::success('Éxito', 'Pregunta frecuente eliminada correctamente');
        return redirect()->route('admin.preguntas-frecuentes.index');
    }
}
