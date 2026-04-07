<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaServicioTecnico;
use App\Models\Reparaciones;
use App\Models\ServicioTecnico;
use Illuminate\Support\Facades\DB;

class SubcontratacionesController extends Controller
{
    /**
     * Mostrar la vista unificada de Subcontrataciones con 3 pestañas:
     * - Técnicos (antes Reparaciones)
     * - Catálogo de Servicios (antes Servicios Técnicos)
     * - Asignación y Precios (antes Técnicos y Servicios)
     */
    public function index()
    {
        // Tab 1: Técnicos
        $tecnicos = Reparaciones::orderBy('nombre')->get();

        // Tab 2: Catálogo de Servicios
        $categorias = CategoriaServicioTecnico::withCount('servicios')
            ->ordenadas()
            ->get();

        $servicios = ServicioTecnico::with('categoria')
            ->ordenados()
            ->get();

        $serviciosPorCategoria = $servicios->groupBy('categoria_id');

        // Tab 3: Asignación y Precios
        $tecnicosConServicios = Reparaciones::withCount('servicios')
            ->orderBy('nombre')
            ->get();

        // Categorías activas para el tab 3
        $categoriasActivas = CategoriaServicioTecnico::activas()
            ->ordenadas()
            ->get();

        return view('admin.subcontrataciones.index', compact(
            'tecnicos',
            'categorias',
            'servicios',
            'serviciosPorCategoria',
            'tecnicosConServicios',
            'categoriasActivas'
        ));
    }
}
