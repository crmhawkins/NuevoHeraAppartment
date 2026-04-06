<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PhotoAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class LimpiezaAnalisisController extends Controller
{
    public function index(Request $request)
    {
        // Obtener empleadas para el filtro
        $empleadas = User::where('role', 'empleada')
            ->orWhere('role', 'limpiadora')
            ->orWhere('role', 'admin')
            ->orderBy('name')
            ->get();

        // Construir query base
        $query = PhotoAnalysis::with(['limpieza.apartamento', 'empleada', 'categoria'])
            ->orderBy('fecha_analisis', 'desc');

        // Aplicar filtros
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha_analisis', '>=', $request->fecha_desde);
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha_analisis', '<=', $request->fecha_hasta);
        }

        if ($request->filled('empleada')) {
            $query->where('empleada_id', $request->empleada);
        }

        if ($request->filled('calidad')) {
            $query->where('calidad_general', $request->calidad);
        }

        // Obtener análisis paginados
        $analisis = $query->paginate(20);

        // Preparar datos para la vista
        $filtros = [
            'fecha_desde' => $request->fecha_desde,
            'fecha_hasta' => $request->fecha_hasta,
            'empleada' => $request->empleada,
            'calidad' => $request->calidad
        ];

        return view('limpiezas.analisis', compact('analisis', 'empleadas', 'filtros'));
    }

    public function estadisticas()
    {
        // Estadísticas generales
        $totalAnalisis = PhotoAnalysis::count();
        $analisisHoy = PhotoAnalysis::whereDate('fecha_analisis', today())->count();
        $analisisSemana = PhotoAnalysis::whereBetween('fecha_analisis', [now()->startOfWeek(), now()->endOfWeek()])->count();
        
        // Calidad general
        $calidadStats = PhotoAnalysis::select('calidad_general', DB::raw('count(*) as total'))
            ->groupBy('calidad_general')
            ->get()
            ->pluck('total', 'calidad_general')
            ->toArray();

        // Responsabilidad
        $bajoResponsabilidad = PhotoAnalysis::where('continuo_bajo_responsabilidad', true)->count();
        $aprobadas = PhotoAnalysis::where('continuo_bajo_responsabilidad', false)->count();

        // Empleadas más activas
        $empleadasActivas = PhotoAnalysis::with('empleada')
            ->select('empleada_id', DB::raw('count(*) as total'))
            ->groupBy('empleada_id')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_analisis' => $totalAnalisis,
            'analisis_hoy' => $analisisHoy,
            'analisis_semana' => $analisisSemana,
            'calidad_stats' => $calidadStats,
            'bajo_responsabilidad' => $bajoResponsabilidad,
            'aprobadas' => $aprobadas,
            'empleadas_activas' => $empleadasActivas
        ]);
    }
}
