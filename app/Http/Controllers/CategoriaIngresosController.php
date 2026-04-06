<?php

namespace App\Http\Controllers;

use App\Models\CategoriaIngresos;
use Illuminate\Http\Request;

class CategoriaIngresosController extends Controller
{
    public function index(Request $request) {
        $search = $request->get('search');
        $sort = $request->get('sort', 'id'); // Default sort column
        $order = $request->get('order', 'asc'); // Default sort order

        $categorias = CategoriaIngresos::where(function ($query) use ($search) {
            $query->where('nombre', 'like', '%'.$search.'%');
        })
        ->orderBy($sort, $order)
        ->paginate(30);
        // $bancos = Bancos::all();
        return view('admin.categoriaIngresos.index', compact('categorias'));
    }

    public function create(){
        return view('admin.categoriaIngresos.create');
    }

    public function store(Request $request){
        $rules = [
            'nombre' => 'required|string|max:255',
            'contabilizar_misma_empresa' => 'boolean',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $ingresoCategoria = CategoriaIngresos::create($validatedData);

        return redirect()->route('admin.categoriaIngresos.index')->with('status', 'Categoria de ingreso creado con éxito!');

    }
    public function edit(CategoriaIngresos $categoria){

        return view('admin.categoriaIngresos.edit', compact('categoria'));
    }

    public function update(Request $request, CategoriaIngresos $categoria){
        $rules = [
            'nombre' => 'required|string|max:255',
            'contabilizar_misma_empresa' => 'boolean',
        ];

        // Validar los datos del formulario
        $validatedData = $request->validate($rules);
        $categoria->update($validatedData);

        return redirect()->route('admin.categoriaIngresos.index')->with('status', 'Categoria de ingreso actualizado con éxito!');

    }
    public function destroy(CategoriaIngresos $categoria){
        $categoria->delete();
        return redirect()->route('admin.categoriaIngresos.index')->with('status', 'Categoria de ingreso eliminada con éxito!');
    }
}
