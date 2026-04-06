<?php

namespace App\Http\Controllers;

use App\Models\PreguntaFrecuente;
use App\Models\PaginaLegal;
use Illuminate\Http\Request;

class PublicPreguntasFrecuentesController extends Controller
{
    /**
     * Mostrar todas las preguntas frecuentes
     */
    public function index()
    {
        $preguntas = PreguntaFrecuente::activas()->ordenadas()->get();
        
        // Agrupar por categoría
        $preguntasPorCategoria = $preguntas->groupBy('categoria');
        
        // Obtener todas las páginas legales para el sidebar
        $paginasSidebar = PaginaLegal::activas()
            ->visiblesEnSidebar()
            ->ordenadas()
            ->get();

        return view('public.preguntas-frecuentes.index', compact('preguntas', 'preguntasPorCategoria', 'paginasSidebar'));
    }
}
