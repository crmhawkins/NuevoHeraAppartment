<?php

namespace App\Http\Controllers;

use App\Models\Bancos;
use Illuminate\Http\Request;

class BancosController extends Controller
{
    public function index(Request $request) {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id'); // Default sort column
        $order = $request->get('order', 'asc'); // Default sort order

        $bancos = Bancos::where(function ($query) use ($search) {
            $query->where('nombre', 'like', '%'.$search.'%');
        })
        ->orderBy($sort, $order)
        ->paginate(30);
        // $bancos = Bancos::all();
        return view('admin.bancos.index', compact('bancos'));
    }

    public function create(){
        return view('admin.bancos.create');
    }

    public function store(Request $request){
        $rules = [
            'nombre' => 'required|string|max:255',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $banco = Bancos::create($validatedData);

        return redirect()->route('admin.bancos.index')->with('status', 'Banco creado con éxito!');

    }
    public function edit(Bancos $banco){

        return view('admin.bancos.edit', compact('banco'));
    }

    public function update(Request $request, Bancos $banco){
        $rules = [
            'nombre' => 'required|string|max:255',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $banco->update([
            'nombre' => $validatedData['nombre']
        ]);

        return redirect()->route('admin.bancos.index')->with('status', 'Banco actualizado con éxito!');

    }
    public function destroy(Bancos $banco){
        $banco->delete();
        return redirect()->route('admin.bancos.index')->with('status', 'Banco eliminado con éxito!');
    }
}
