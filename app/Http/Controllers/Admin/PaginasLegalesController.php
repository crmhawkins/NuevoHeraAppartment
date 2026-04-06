<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaginaLegal;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;
use Illuminate\Support\Str;

class PaginasLegalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paginas = PaginaLegal::ordenadas()->get();
        return view('admin.paginas-legales.index', compact('paginas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.paginas-legales.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:paginas_legales,slug',
            'contenido' => 'required|string',
            'fecha_actualizacion' => 'nullable|date',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
            'mostrar_en_sidebar' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['titulo']);
        }

        $validated['fecha_actualizacion'] = $validated['fecha_actualizacion'] ?? now();

        PaginaLegal::create($validated);

        Alert::success('Éxito', 'Página legal creada correctamente');
        return redirect()->route('admin.paginas-legales.index');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $pagina = PaginaLegal::findOrFail($id);
        return view('admin.paginas-legales.show', compact('pagina'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $pagina = PaginaLegal::findOrFail($id);
        return view('admin.paginas-legales.edit', ['paginasLegale' => $pagina]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $pagina = PaginaLegal::findOrFail($id);
        
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:paginas_legales,slug,' . $pagina->id,
            'contenido' => 'required|string',
            'fecha_actualizacion' => 'nullable|date',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
            'mostrar_en_sidebar' => 'nullable|boolean',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['titulo']);
        }

        $pagina->update($validated);

        Alert::success('Éxito', 'Página legal actualizada correctamente');
        return redirect()->route('admin.paginas-legales.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $pagina = PaginaLegal::findOrFail($id);
        $pagina->delete();

        Alert::success('Éxito', 'Página legal eliminada correctamente');
        return redirect()->route('admin.paginas-legales.index');
    }
}
