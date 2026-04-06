<?php

namespace App\Http\Controllers;

use App\Models\PaginaLegal;
use Illuminate\Http\Request;

class PublicPaginaLegalController extends Controller
{
    /**
     * Mostrar una página legal por slug
     */
    public function show($slug)
    {
        $pagina = PaginaLegal::where('slug', $slug)
            ->where('activo', true)
            ->firstOrFail();

        // Obtener todas las páginas para el sidebar
        $paginasSidebar = PaginaLegal::activas()
            ->visiblesEnSidebar()
            ->ordenadas()
            ->get();

        return view('public.pagina-legal.show', compact('pagina', 'paginasSidebar'));
    }
}
