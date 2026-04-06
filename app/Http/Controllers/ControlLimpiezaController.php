<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ControlLimpieza;

class ControlLimpiezaController extends Controller
{
    public function index()
    {
        $controles = ControlLimpieza::all();
        return view('controles_limpieza.index', compact('controles'));
    }

    public function create()
    {
        return view('controles_limpieza.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'apartamento_limpieza_id' => 'required',
            'item_checklist_id' => 'required',
            'estado' => 'required|boolean',
        ]);

        ControlLimpieza::create($request->all());

        return redirect()->route('controles_limpieza.index')->with('success', 'Control de limpieza creado con éxito.');
    }

    public function edit($id)
    {
        $control = ControlLimpieza::findOrFail($id);
        return view('controles_limpieza.edit', compact('control'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'apartamento_limpieza_id' => 'required',
            'item_checklist_id' => 'required',
            'estado' => 'required|boolean',
        ]);

        $control = ControlLimpieza::findOrFail($id);
        $control->update($request->all());

        return redirect()->route('controles_limpieza.index')->with('success', 'Control de limpieza actualizado con éxito.');
    }

    public function destroy($id)
    {
        $control = ControlLimpieza::findOrFail($id);
        $control->delete();

        return redirect()->route('controles_limpieza.index')->with('success', 'Control de limpieza eliminado con éxito.');
    }
}
