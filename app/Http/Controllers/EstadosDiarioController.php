<?php

namespace App\Http\Controllers;

use App\Models\EstadosDiario;
use Illuminate\Http\Request;

class EstadosDiarioController extends Controller
{
     // Método para mostrar la lista de estados
     public function index(Request $request)
     {
         // Filtro de búsqueda
         $search = $request->input('search');
         $query = EstadosDiario::query();
 
         if ($search) {
             $query->where('nombre', 'like', '%' . $search . '%');
         }
 
         // Obtener los estados con paginación
         $estados = $query->orderBy('id', 'asc')->paginate(10);
 
         return view('admin.contabilidad.estadosDiarioCaja.index', compact('estados'));
     }
 
     // Método para mostrar el formulario de creación
     public function create()
     {
         return view('admin.contabilidad.estadosDiarioCaja.create');
     }
 
     // Método para almacenar un nuevo estado en la base de datos
     public function store(Request $request)
     {
         // Validación de datos
         $request->validate([
             'nombre' => 'required|string|max:255'
         ]);
 
         // Crear un nuevo estado
         EstadosDiario::create([
             'nombre' => $request->nombre
         ]);
 
         // Redirigir con un mensaje de éxito
         return redirect()->route('admin.estadosDiario.index')->with('status', 'Estado creado correctamente');
     }
 
     // Método para mostrar el formulario de edición
     public function edit($id)
     {
         // Buscar el estado por ID
         $estado = EstadosDiario::findOrFail($id);
 
         return view('admin.contabilidad.estadosDiarioCaja.edit', compact('estado'));
     }
 
     // Método para actualizar el estado en la base de datos
     public function update(Request $request, $id)
     {
         // Validación de datos
         $request->validate([
             'nombre' => 'required|string|max:255'
         ]);
 
         // Buscar el estado y actualizar
         $estado = EstadosDiario::findOrFail($id);
         $estado->update([
             'nombre' => $request->nombre
         ]);
 
         // Redirigir con un mensaje de éxito
         return redirect()->route('admin.estadosDiario.index')->with('status', 'Estado actualizado correctamente');
     }
 
     // Método para eliminar un estado
     public function destroy($id)
     {
         // Buscar el estado y eliminar
         $estado = EstadosDiario::findOrFail($id);
         $estado->delete();
 
         // Redirigir con un mensaje de éxito
         return redirect()->route('admin.estadosDiario.index')->with('status', 'Estado eliminado correctamente');
     }
}
