<?php

namespace App\Http\Controllers;

use App\Models\HistorialDescuento;
use Illuminate\Http\Request;

class HistorialDescuentoController extends Controller
{
    /**
     * Mostrar el historial de descuentos
     */
    public function index(Request $request)
    {
        $query = HistorialDescuento::with(['apartamento', 'tarifa', 'configuracionDescuento']);

        // Filtros
        if ($request->filled('fecha')) {
            $query->where('fecha_aplicacion', $request->fecha);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('apartamento')) {
            $query->where('apartamento_id', $request->apartamento);
        }

        if ($request->filled('edificio')) {
            $query->whereHas('configuracionDescuento.edificio', function($q) use ($request) {
                $q->where('id', $request->edificio);
            });
        }

        $historial = $query->orderBy('created_at', 'desc')->paginate(20);

        // Estadísticas
        $estadisticas = [
            'total' => $query->count(),
            'aplicados' => $query->where('estado', 'aplicado')->count(),
            'pendientes' => $query->where('estado', 'pendiente')->count(),
            'errores' => $query->where('estado', 'error')->count(),
            'ahorro_total' => $query->where('estado', 'aplicado')->sum('ahorro_total')
        ];

        return view('admin.historial-descuentos.index', compact('historial', 'estadisticas'));
    }

    /**
     * Mostrar detalles de un registro específico
     */
    public function show(HistorialDescuento $historial)
    {
        $historial->load(['apartamento', 'tarifa', 'configuracionDescuento.edificio']);
        
        return view('admin.historial-descuentos.show', compact('historial'));
    }

    /**
     * Obtener datos del momento en formato JSON para AJAX
     */
    public function getDatosMomento(HistorialDescuento $historial)
    {
        if (!$historial->datos_momento) {
            return response()->json(['error' => 'No hay datos del momento disponibles']);
        }

        $verificacion = $historial->verificarRequisitosCumplidos();
        
        return response()->json([
            'datos' => $historial->datos_momento,
            'verificacion' => $verificacion,
            'resumen' => $historial->resumen_datos_momento
        ]);
    }
}
