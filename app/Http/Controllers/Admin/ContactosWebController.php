<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactoWeb;
use Illuminate\Http\Request;

class ContactosWebController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contactos = ContactoWeb::orderBy('created_at', 'desc')
            ->with('leidoPor')
            ->paginate(20);

        $noLeidos = ContactoWeb::where('leido', false)->count();

        return view('admin.contactos-web.index', compact('contactos', 'noLeidos'));
    }

    /**
     * Display the specified resource.
     */
    public function show(ContactoWeb $contactos_web)
    {
        $contacto = $contactos_web->load('leidoPor');
        
        // Marcar como leído si no lo está
        if (!$contacto->leido) {
            $contacto->marcarComoLeido(auth()->id());
        }

        return view('admin.contactos-web.show', compact('contacto'));
    }

    /**
     * Marcar contacto como leído/no leído
     */
    public function toggleLeido(Request $request, ContactoWeb $contactos_web)
    {
        $contacto = $contactos_web;
        
        if ($contacto->leido) {
            $contacto->update([
                'leido' => false,
                'leido_at' => null,
                'leido_por' => null,
            ]);
        } else {
            $contacto->marcarComoLeido(auth()->id());
        }

        return back()->with('success', 'Estado actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ContactoWeb $contactos_web)
    {
        $contactos_web->delete();

        return redirect()->route('admin.contactos-web.index')
            ->with('success', 'Contacto eliminado correctamente.');
    }
}
