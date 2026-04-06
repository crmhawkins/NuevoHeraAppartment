<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FaqApartamento;
use App\Models\Apartamento;
use Illuminate\Http\Request;
use RealRashid\SweetAlert\Facades\Alert;

class AdminFaqApartamentosController extends Controller
{
    /**
     * Mostrar FAQs de un apartamento
     */
    public function index($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $faqs = FaqApartamento::where('apartamento_id', $apartamentoId)
            ->orderBy('orden')
            ->orderBy('pregunta')
            ->get();
        
        return view('admin.faq-apartamentos.index', compact('apartamento', 'faqs'));
    }

    /**
     * Mostrar formulario para crear FAQ
     */
    public function create($apartamentoId)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        return view('admin.faq-apartamentos.create', compact('apartamento'));
    }

    /**
     * Guardar nuevo FAQ
     */
    public function store(Request $request, $apartamentoId)
    {
        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $validated['apartamento_id'] = $apartamentoId;
        $validated['activo'] = $request->has('activo');

        FaqApartamento::create($validated);

        Alert::success('Éxito', 'Pregunta frecuente creada correctamente');
        return redirect()->route('admin.faq-apartamentos.index', $apartamentoId);
    }

    /**
     * Mostrar formulario para editar FAQ
     */
    public function edit($apartamentoId, $id)
    {
        $apartamento = Apartamento::findOrFail($apartamentoId);
        $faq = FaqApartamento::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);
        
        return view('admin.faq-apartamentos.edit', compact('apartamento', 'faq'));
    }

    /**
     * Actualizar FAQ
     */
    public function update(Request $request, $apartamentoId, $id)
    {
        $faq = FaqApartamento::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);

        $validated = $request->validate([
            'pregunta' => 'required|string|max:500',
            'respuesta' => 'required|string',
            'orden' => 'nullable|integer|min:0',
            'activo' => 'nullable|boolean',
        ]);

        $validated['activo'] = $request->has('activo');

        $faq->update($validated);

        Alert::success('Éxito', 'Pregunta frecuente actualizada correctamente');
        return redirect()->route('admin.faq-apartamentos.index', $apartamentoId);
    }

    /**
     * Eliminar FAQ
     */
    public function destroy($apartamentoId, $id)
    {
        $faq = FaqApartamento::where('apartamento_id', $apartamentoId)
            ->findOrFail($id);
        
        $faq->delete();

        Alert::success('Éxito', 'Pregunta frecuente eliminada correctamente');
        return redirect()->route('admin.faq-apartamentos.index', $apartamentoId);
    }
}
