<?php

namespace App\Http\Controllers;

use App\Models\PoliticaCancelacion;
use Illuminate\Http\Request;

class PublicPoliticaCancelacionController extends Controller
{
    /**
     * Mostrar la política de cancelaciones pública
     */
    public function index()
    {
        $politica = PoliticaCancelacion::activa();

        if (!$politica) {
            abort(404, 'Política de cancelaciones no encontrada');
        }

        // Obtener todas las páginas legales para el sidebar
        $paginasSidebar = \App\Models\PaginaLegal::activas()
            ->visiblesEnSidebar()
            ->ordenadas()
            ->get();

        return view('public.politica-cancelacion.index', compact('politica', 'paginasSidebar'));
    }
}
