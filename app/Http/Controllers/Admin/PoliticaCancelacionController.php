<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoliticaCancelacion;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class PoliticaCancelacionController extends Controller
{
    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        $politica = PoliticaCancelacion::first();
        
        if (!$politica) {
            $politica = PoliticaCancelacion::create([
                'titulo' => 'Política de Cancelaciones y Devoluciones',
                'contenido' => '',
                'fecha_actualizacion' => now(),
                'activo' => true,
            ]);
        }

        return view('admin.politica-cancelacion.edit', compact('politica'));
    }

    /**
     * Actualizar la política
     */
    public function update(Request $request)
    {
        $politica = PoliticaCancelacion::firstOrFail();

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'contenido' => 'required|string',
            'fecha_actualizacion' => 'nullable|date',
            'activo' => 'nullable|boolean',
        ]);

        $validated['fecha_actualizacion'] = $validated['fecha_actualizacion'] ?? now();

        $politica->update($validated);

        Alert::success('Éxito', 'Política de cancelaciones actualizada correctamente');
        return redirect()->route('admin.politica-cancelacion.edit');
    }
}
