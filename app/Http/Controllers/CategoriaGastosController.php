<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGastos;
use Illuminate\Http\Request;

class CategoriaGastosController extends Controller
{
    public function index(Request $request) {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id'); // Default sort column
        $order = $request->get('order', 'asc'); // Default sort order

        $categorias = CategoriaGastos::where(function ($query) use ($search) {
            $query->where('nombre', 'like', '%'.$search.'%');
        })
        ->orderBy($sort, $order)
        ->paginate(30);
        // $bancos = Bancos::all();
        return view('admin.categoriaGastos.index', compact('categorias'));
    }

    public function create(){
        return view('admin.categoriaGastos.create');
    }

    public function store(Request $request){
        $rules = [
            'nombre' => 'required|string|max:255',
            'contabilizar_misma_empresa' => 'boolean',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $banco = CategoriaGastos::create($validatedData);

        return redirect()->route('admin.categoriaGastos.index')->with('status', 'Categoria de gasto creado con éxito!');

    }
    public function edit(CategoriaGastos $categoria){

        return view('admin.categoriaGastos.edit', compact('categoria'));
    }

    public function update(Request $request, CategoriaGastos $categoria){
        $rules = [
            'nombre' => 'required|string|max:255',
            'contabilizar_misma_empresa' => 'boolean',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $categoria->update($validatedData);

        return redirect()->route('admin.categoriaGastos.index')->with('status', 'Categoria de gasto actualizado con éxito!');

    }
    public function destroy(CategoriaGastos $categoria){
        $categoria->delete();
        return redirect()->route('admin.categoriaGastos.index')->with('status', 'Categoria de gasto eliminada con éxito!');
    }
}
