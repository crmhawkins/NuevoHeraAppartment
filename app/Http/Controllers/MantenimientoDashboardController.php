<?php

namespace App\Http\Controllers;

use App\Models\ApartamentoLimpieza;
use App\Models\Incidencia;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class MantenimientoDashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:MANTENIMIENTO');
    }

    /**
     * Dashboard principal para el usuario de mantenimiento.
     */
    public function index()
    {
        $user = Auth::user();
        $hoy = Carbon::today();

        // Apartamentos con limpieza hoy: "En Limpieza" (2) y "Limpio" (3)
        $apartamentosLimpiadosHoy = ApartamentoLimpieza::whereDate('fecha_comienzo', $hoy)
            ->whereIn('status_id', [2, 3]) // 2 = En Limpieza, 3 = Limpio
            ->whereNotNull('apartamento_id')
            ->with(['apartamento.edificioName', 'empleada', 'reserva'])
            ->orderByDesc('fecha_fin')
            ->orderByDesc('updated_at')
            ->get();

        // Reservas que entran hoy
        $reservasEntradaHoy = Reserva::with(['apartamento.edificioName', 'cliente', 'estado'])
            ->whereDate('fecha_entrada', $hoy)
            ->where('estado_id', '!=', 4) // Excluir canceladas
            ->orderBy('fecha_entrada')
            ->get();

        // Incidencias pendientes (sin ver / por revisar)
        $incidenciasPendientesCount = Incidencia::where('estado', 'pendiente')->count();
        $incidenciasPendientes = Incidencia::where('estado', 'pendiente')
            ->with(['apartamento', 'zonaComun'])
            ->orderByRaw("CASE WHEN prioridad = 'urgente' THEN 1 WHEN prioridad = 'alta' THEN 2 WHEN prioridad = 'media' THEN 3 ELSE 4 END")
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $datos = [
            'hoy' => $hoy->format('d/m/Y'),
            'diaSemana' => $hoy->locale('es')->dayName,
            'apartamentosLimpiadosHoy' => $apartamentosLimpiadosHoy,
            'reservasEntradaHoy' => $reservasEntradaHoy,
            'incidenciasPendientesCount' => $incidenciasPendientesCount,
            'incidenciasPendientes' => $incidenciasPendientes,
        ];

        return view('mantenimiento.dashboard', compact('datos'));
    }
}
