<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReservationConflictAlert;
use App\Models\Reserva;
use App\Models\Apartamento;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ConflictosReservasController extends Controller
{
    /**
     * Dashboard de conflictos de reservas.
     */
    public function index()
    {
        $ahora = Carbon::now();

        // Conflictos activos (sin resolver)
        $activos = ReservationConflictAlert::whereNull('resolved_at')
            ->orderBy('created_at', 'desc')
            ->get();

        // Conflictos resueltos
        $resueltos = ReservationConflictAlert::whereNotNull('resolved_at')
            ->orderBy('resolved_at', 'desc')
            ->limit(50)
            ->get();

        // Estadísticas
        $totalActivos = $activos->count();
        $resueltosHoy = ReservationConflictAlert::whereNotNull('resolved_at')
            ->whereDate('resolved_at', $ahora->toDateString())
            ->count();
        $totalEsteMes = ReservationConflictAlert::whereMonth('created_at', $ahora->month)
            ->whereYear('created_at', $ahora->year)
            ->count();

        // Precargar datos de apartamentos y reservas para cada conflicto
        $todosConflictos = $activos->merge($resueltos);
        $apartamentoIds = $todosConflictos->pluck('apartamento_id')->unique()->filter();
        $apartamentos = Apartamento::whereIn('id', $apartamentoIds)->pluck('nombre', 'id');

        $reservaIds = $todosConflictos->flatMap(function ($c) {
            return is_array($c->reserva_ids) ? $c->reserva_ids : [];
        })->unique()->filter();
        $reservas = Reserva::whereIn('id', $reservaIds)
            ->select('id', 'fecha_entrada', 'fecha_salida', 'cliente_id', 'apartamento_id')
            ->with('cliente:id,nombre,apellidos')
            ->get()
            ->keyBy('id');

        return view('admin.conflictos-reservas.index', compact(
            'activos',
            'resueltos',
            'totalActivos',
            'resueltosHoy',
            'totalEsteMes',
            'apartamentos',
            'reservas'
        ));
    }

    /**
     * Marcar un conflicto como resuelto.
     */
    public function resolver(Request $request, $id)
    {
        $conflicto = ReservationConflictAlert::findOrFail($id);

        $conflicto->update([
            'resolved_at' => Carbon::now(),
        ]);

        return redirect()
            ->route('admin.conflictos-reservas.index')
            ->with('swal_success', 'Conflicto marcado como resuelto correctamente.');
    }

    /**
     * Detalle de un conflicto con ambas reservas lado a lado.
     */
    public function detalle($id)
    {
        $conflicto = ReservationConflictAlert::findOrFail($id);

        $apartamento = Apartamento::find($conflicto->apartamento_id);

        $reservaIds = is_array($conflicto->reserva_ids) ? $conflicto->reserva_ids : [];
        $reservas = Reserva::whereIn('id', $reservaIds)
            ->with(['cliente', 'apartamento'])
            ->get();

        return view('admin.conflictos-reservas.detalle', compact(
            'conflicto',
            'apartamento',
            'reservas'
        ));
    }
}
