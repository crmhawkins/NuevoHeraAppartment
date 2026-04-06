<?php

namespace App\Http\Controllers;

use App\Models\ApartamentoLimpieza;
use Illuminate\Http\Request;

class MantenimientoLimpiezaController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:MANTENIMIENTO');
    }

    /**
     * Ver detalle de una limpieza completada (solo lectura). Misma vista que gestión.
     */
    public function show($id)
    {
        $apartamentoLimpieza = ApartamentoLimpieza::with([
            'apartamento.edificio',
            'zonaComun',
            'empleada',
            'estado',
            'reserva',
            'fotos' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        $todasLasFotos = $apartamentoLimpieza->fotos;

        $checklists = [];
        if ($apartamentoLimpieza->apartamento && $apartamentoLimpieza->apartamento->edificio_id) {
            $checklists = \App\Models\Checklist::with('items')->where('edificio_id', $apartamentoLimpieza->apartamento->edificio_id)->get();
        }

        $itemsExistentes = [];
        if ($apartamentoLimpieza->id) {
            $itemsExistentes = \App\Models\ApartamentoLimpiezaItem::where('id_limpieza', $apartamentoLimpieza->id)->get();
        }

        return view('gestion.ver-limpieza', compact(
            'apartamentoLimpieza',
            'checklists',
            'itemsExistentes',
            'todasLasFotos'
        ) + [
            'back_route' => 'mantenimiento.dashboard',
            'back_label' => 'Volver al inicio',
        ]);
    }
}
