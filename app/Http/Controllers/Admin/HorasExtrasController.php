<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HorasExtras;
use App\Models\User;
use App\Models\TurnoTrabajo;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HorasExtrasController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ADMIN']);
    }

    /**
     * Mostrar listado de horas extras
     */
    public function index(Request $request)
    {
        $query = HorasExtras::with(['user', 'turno', 'aprobador']);

        // Filtros
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        $horasExtras = $query->orderBy('fecha', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Obtener empleadas para el filtro
        $empleadas = User::where('role', 'LIMPIEZA')
            ->where('inactive', null)
            ->orderBy('name')
            ->get();

        // Estadísticas
        $estadisticas = [
            'total' => HorasExtras::count(),
            'pendientes' => HorasExtras::pendientes()->count(),
            'aprobadas' => HorasExtras::aprobadas()->count(),
            'rechazadas' => HorasExtras::rechazadas()->count(),
            'total_horas_extras' => HorasExtras::aprobadas()->sum('horas_extras'),
            'total_horas_extras_pendientes' => HorasExtras::pendientes()->sum('horas_extras')
        ];

        return view('admin.horas-extras.index', compact(
            'horasExtras', 
            'empleadas', 
            'estadisticas'
        ));
    }

    /**
     * Mostrar detalles de horas extras
     */
    public function show(HorasExtras $horasExtras)
    {
        $horasExtras->load(['user', 'turno', 'aprobador']);
        
        return view('admin.horas-extras.show', compact('horasExtras'));
    }

    /**
     * Aprobar horas extras
     */
    public function aprobar(Request $request, HorasExtras $horasExtras)
    {
        $request->validate([
            'observaciones_admin' => 'nullable|string|max:1000'
        ]);

        try {
            $horasExtras->aprobar(Auth::id(), $request->observaciones_admin);
            
            return response()->json([
                'success' => true,
                'message' => 'Horas extras aprobadas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error aprobando horas extras: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar las horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar horas extras
     */
    public function rechazar(Request $request, HorasExtras $horasExtras)
    {
        $request->validate([
            'observaciones_admin' => 'required|string|max:1000'
        ]);

        try {
            $horasExtras->rechazar(Auth::id(), $request->observaciones_admin);
            
            return response()->json([
                'success' => true,
                'message' => 'Horas extras rechazadas exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error rechazando horas extras: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar las horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Aprobar múltiples horas extras
     */
    public function aprobarMultiples(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:horas_extras,id',
            'observaciones_admin' => 'nullable|string|max:1000'
        ]);

        try {
            $horasExtras = HorasExtras::whereIn('id', $request->ids)
                ->where('estado', HorasExtras::ESTADO_PENDIENTE)
                ->get();

            $aprobadas = 0;
            foreach ($horasExtras as $horaExtra) {
                $horaExtra->aprobar(Auth::id(), $request->observaciones_admin);
                $aprobadas++;
            }

            return response()->json([
                'success' => true,
                'message' => "Se aprobaron {$aprobadas} registros de horas extras"
            ]);

        } catch (\Exception $e) {
            Log::error('Error aprobando múltiples horas extras: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al aprobar las horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar múltiples horas extras
     */
    public function rechazarMultiples(Request $request)
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:horas_extras,id',
            'observaciones_admin' => 'required|string|max:1000'
        ]);

        try {
            $horasExtras = HorasExtras::whereIn('id', $request->ids)
                ->where('estado', HorasExtras::ESTADO_PENDIENTE)
                ->get();

            $rechazadas = 0;
            foreach ($horasExtras as $horaExtra) {
                $horaExtra->rechazar(Auth::id(), $request->observaciones_admin);
                $rechazadas++;
            }

            return response()->json([
                'success' => true,
                'message' => "Se rechazaron {$rechazadas} registros de horas extras"
            ]);

        } catch (\Exception $e) {
            Log::error('Error rechazando múltiples horas extras: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar las horas extras: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Exportar horas extras a Excel
     */
    public function exportar(Request $request)
    {
        $query = HorasExtras::with(['user', 'turno', 'aprobador']);

        // Aplicar mismos filtros que en index
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('fecha_inicio')) {
            $query->whereDate('fecha', '>=', $request->fecha_inicio);
        }

        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha', '<=', $request->fecha_fin);
        }

        $horasExtras = $query->orderBy('fecha', 'desc')->get();

        // Crear archivo Excel
        $filename = 'horas_extras_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        
        // Aquí implementarías la exportación a Excel usando Laravel Excel
        // Por ahora retornamos un JSON para testing
        return response()->json([
            'success' => true,
            'message' => 'Exportación iniciada',
            'filename' => $filename,
            'total_registros' => $horasExtras->count()
        ]);
    }

    /**
     * Obtener estadísticas de horas extras
     */
    public function estadisticas(Request $request)
    {
        $fechaInicio = $request->get('fecha_inicio', now()->startOfMonth());
        $fechaFin = $request->get('fecha_fin', now()->endOfMonth());

        $horasExtras = HorasExtras::porRangoFechas($fechaInicio, $fechaFin)
            ->with(['user'])
            ->get();

        $estadisticas = [
            'total_registros' => $horasExtras->count(),
            'total_horas_extras' => $horasExtras->sum('horas_extras'),
            'total_horas_extras_aprobadas' => $horasExtras->where('estado', HorasExtras::ESTADO_APROBADA)->sum('horas_extras'),
            'total_horas_extras_pendientes' => $horasExtras->where('estado', HorasExtras::ESTADO_PENDIENTE)->sum('horas_extras'),
            'por_empleada' => $horasExtras->groupBy('user_id')->map(function($horasEmpleada) {
                $empleada = $horasEmpleada->first()->user;
                return [
                    'nombre' => $empleada->name,
                    'total_registros' => $horasEmpleada->count(),
                    'total_horas_extras' => $horasEmpleada->sum('horas_extras'),
                    'horas_aprobadas' => $horasEmpleada->where('estado', HorasExtras::ESTADO_APROBADA)->sum('horas_extras'),
                    'horas_pendientes' => $horasEmpleada->where('estado', HorasExtras::ESTADO_PENDIENTE)->sum('horas_extras')
                ];
            }),
            'por_dia' => $horasExtras->groupBy(function($horaExtra) {
                return $horaExtra->fecha->format('Y-m-d');
            })->map(function($horasDia) {
                return [
                    'fecha' => $horasDia->first()->fecha->format('d/m/Y'),
                    'total_registros' => $horasDia->count(),
                    'total_horas_extras' => $horasDia->sum('horas_extras')
                ];
            })
        ];

        return response()->json($estadisticas);
    }
}