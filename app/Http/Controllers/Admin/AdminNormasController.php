<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NormaCasa;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AdminNormasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $normas = NormaCasa::orderBy('orden')
            ->orderBy('titulo')
            ->get();
        
        return view('admin.normas-casa.index', compact('normas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.normas-casa.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'icono' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        NormaCasa::create([
            'icono' => $request->icono,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden ?? 0,
            'activo' => $request->has('activo'),
        ]);

        Alert::success('Éxito', 'Norma de la casa creada correctamente');
        return redirect()->route('admin.normas-casa.index');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $norma = NormaCasa::findOrFail($id);
        return view('admin.normas-casa.show', compact('norma'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $norma = NormaCasa::findOrFail($id);
        return view('admin.normas-casa.edit', compact('norma'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'icono' => 'nullable|string|max:255',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $norma = NormaCasa::findOrFail($id);
        $norma->update([
            'icono' => $request->icono,
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
            'orden' => $request->orden ?? 0,
            'activo' => $request->has('activo'),
        ]);

        Alert::success('Éxito', 'Norma de la casa actualizada correctamente');
        return redirect()->route('admin.normas-casa.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $norma = NormaCasa::findOrFail($id);
        $norma->delete();

        Alert::success('Éxito', 'Norma de la casa eliminada correctamente');
        return redirect()->route('admin.normas-casa.index');
    }
}
