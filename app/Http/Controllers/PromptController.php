<?php

namespace App\Http\Controllers;

use App\Models\PromptAsistente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PromptController extends Controller
{
    /**
     * Mostrar y editar el prompt de un tipo (whatsapp o channex).
     */
    public function edit(string $tipo)
    {
        if (!in_array($tipo, ['whatsapp', 'channex'])) {
            abort(404);
        }

        $prompt = PromptAsistente::where('tipo', $tipo)->first();

        // Si no existe prompt para este tipo, buscar el genérico (retrocompatible)
        if (!$prompt && $tipo === 'whatsapp') {
            $prompt = PromptAsistente::whereNull('tipo')->orWhere('tipo', '')->first();
        }

        $titulo = $tipo === 'whatsapp' ? 'Prompt WhatsApp' : 'Prompt Channex (Booking/Airbnb)';
        $descripcion = $tipo === 'whatsapp'
            ? 'Este prompt se envía a la IA cada vez que un huésped escribe por WhatsApp. Define el comportamiento, tono y reglas del asistente virtual.'
            : 'Este prompt se envía a la IA cada vez que llega un mensaje de un huésped vía Booking o Airbnb (Channex). Define cómo responde el asistente.';

        return view('admin.prompts.edit', compact('prompt', 'tipo', 'titulo', 'descripcion'));
    }

    /**
     * Guardar el prompt.
     */
    public function update(Request $request, string $tipo)
    {
        if (!in_array($tipo, ['whatsapp', 'channex'])) {
            abort(404);
        }

        $request->validate([
            'prompt' => 'required|string|min:10',
        ], [
            'prompt.required' => 'El prompt no puede estar vacío.',
            'prompt.min' => 'El prompt debe tener al menos 10 caracteres.',
        ]);

        $prompt = PromptAsistente::where('tipo', $tipo)->first();

        if (!$prompt && $tipo === 'whatsapp') {
            // Actualizar el prompt genérico existente añadiéndole tipo
            $prompt = PromptAsistente::whereNull('tipo')->orWhere('tipo', '')->first();
            if ($prompt) {
                $prompt->update(['prompt' => $request->prompt, 'tipo' => $tipo]);
            }
        }

        if (!$prompt) {
            // Crear nuevo
            $prompt = PromptAsistente::create([
                'prompt' => $request->prompt,
                'tipo' => $tipo,
            ]);
        } else {
            $prompt->update(['prompt' => $request->prompt]);
        }

        Log::info("[Prompt] Actualizado prompt {$tipo}", [
            'prompt_id' => $prompt->id,
            'length' => strlen($request->prompt),
        ]);

        return redirect()->back()->with('success', 'Prompt actualizado correctamente.');
    }
}
