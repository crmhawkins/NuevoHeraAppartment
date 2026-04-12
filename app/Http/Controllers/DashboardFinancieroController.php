<?php

namespace App\Http\Controllers;

use App\Models\Invoices;
use App\Models\Ingresos;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardFinancieroController extends Controller
{
    public function index(Request $request)
    {
        $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->format('Y-m-d'));
        $fechaHasta = $request->get('fecha_hasta', now()->format('Y-m-d'));
        $estado = $request->get('estado', 'todos');

        // Totales
        $queryBase = Invoices::whereBetween('fecha', [$fechaDesde, $fechaHasta]);

        $totalFacturado = (clone $queryBase)->sum('total');
        $totalCobrado = (clone $queryBase)->where('invoice_status_id', 6)->sum('total');
        $totalPendiente = (clone $queryBase)->whereIn('invoice_status_id', [1, 3, 5])->sum('total');
        $totalCancelado = (clone $queryBase)->where('invoice_status_id', 7)->sum('total');
        $numFacturas = (clone $queryBase)->count();

        // Facturas con filtro de estado
        $queryFacturas = Invoices::with(['cliente', 'reserva', 'estado'])
            ->whereBetween('fecha', [$fechaDesde, $fechaHasta]);

        if ($estado === 'pendiente') {
            $queryFacturas->whereIn('invoice_status_id', [1, 3, 5]);
        } elseif ($estado === 'cobrada') {
            $queryFacturas->where('invoice_status_id', 6);
        } elseif ($estado === 'cancelada') {
            $queryFacturas->where('invoice_status_id', 7);
        }

        $facturas = $queryFacturas->orderBy('fecha', 'desc')->paginate(25);

        // Ingresos por mes (últimos 6 meses)
        $ingresosPorMes = DB::table('ingresos')
            ->selectRaw('DATE_FORMAT(date, "%Y-%m") as mes, SUM(quantity) as total')
            ->where('date', '>=', now()->subMonths(6)->startOfMonth())
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Ingresos por canal (último mes)
        $ingresosPorCanal = DB::table('ingresos')
            ->selectRaw('
                CASE
                    WHEN title LIKE "%booking%" THEN "Booking"
                    WHEN title LIKE "%airbnb%" THEN "Airbnb"
                    WHEN title LIKE "%stripe%" THEN "Web (Stripe)"
                    WHEN title LIKE "%agoda%" THEN "Agoda"
                    ELSE "Otros"
                END as canal,
                SUM(quantity) as total,
                COUNT(*) as num
            ')
            ->where('date', '>=', now()->startOfMonth())
            ->groupBy('canal')
            ->orderBy('total', 'desc')
            ->get();

        // Facturas pendientes más antiguas (>7 días)
        $facturasAntiguas = Invoices::with(['cliente', 'reserva'])
            ->whereIn('invoice_status_id', [1, 3, 5])
            ->where('fecha', '<', now()->subDays(7))
            ->orderBy('fecha', 'asc')
            ->limit(10)
            ->get();

        return view('admin.tesoreria.dashboard-financiero', compact(
            'totalFacturado', 'totalCobrado', 'totalPendiente', 'totalCancelado',
            'numFacturas', 'facturas', 'ingresosPorMes', 'ingresosPorCanal',
            'facturasAntiguas', 'fechaDesde', 'fechaHasta', 'estado'
        ));
    }

    /**
     * Cambiar estado de una factura (AJAX).
     */
    public function cambiarEstado(Request $request, $id)
    {
        $request->validate([
            'estado' => 'required|in:pendiente,cobrada,cancelada',
        ]);

        $factura = Invoices::findOrFail($id);
        $estadoMap = [
            'pendiente' => 1,
            'cobrada' => 6,
            'cancelada' => 7,
        ];

        $nuevoEstado = $estadoMap[$request->estado];
        $factura->update([
            'invoice_status_id' => $nuevoEstado,
            'fecha_cobro' => $request->estado === 'cobrada' ? now() : $factura->fecha_cobro,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Factura #{$factura->reference} marcada como {$request->estado}",
        ]);
    }
}
