<?php

namespace App\Http\Controllers;

use App\Models\Apartamento;
use App\Models\ApartamentoLimpieza;
use App\Models\Checklist;
use App\Models\Photo;
use Illuminate\Http\Request;
use App\Models\ApartamentoLimpiezaItem;
use App\Models\Reserva;

class ApartamentoLimpiezaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $apartamentoLimpieza = ApartamentoLimpieza::where('id', $id)->first();
        $reserva_id = $apartamentoLimpieza->reserva_id;

        $reserva = Reserva::findOrFail($reserva_id);

        // Obtener items existentes de la limpieza
        $apartamentoLimpiezaItems = ApartamentoLimpiezaItem::with(['checklist', 'item'])
            ->where('id_limpieza', $apartamentoLimpieza->id)
            ->get();

        $itemsExistentes = $apartamentoLimpiezaItems->pluck('estado', 'item_id')->toArray();

        // Obtener fotos con categoría
        $fotos = Photo::where('limpieza_id', $apartamentoLimpieza->id)->with('categoria')->get();

        // Obtener el edificio_id correctamente
        $apartamento = Apartamento::findOrFail($apartamentoLimpieza->apartamento_id);
        $edificioId = $apartamento->edificio_id;

        // Obtener checklists del edificio con items
        $checklists = Checklist::with('items')->where('edificio_id', $edificioId)->get();

        return view('admin.apartamentos.limpieza-show', compact('apartamentoLimpieza', 'id', 'checklists', 'itemsExistentes', 'fotos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ApartamentoLimpieza $apartamentoLimpieza)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ApartamentoLimpieza $apartamentoLimpieza)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ApartamentoLimpieza $apartamentoLimpieza)
    {
        //
    }
}
