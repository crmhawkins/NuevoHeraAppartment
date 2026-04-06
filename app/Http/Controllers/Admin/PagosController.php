<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pago;
use App\Models\IntentoPago;
use Illuminate\Http\Request;

class PagosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pagos = Pago::with(['reserva', 'cliente'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $estadisticas = [
            'total' => Pago::count(),
            'completados' => Pago::completados()->count(),
            'pendientes' => Pago::pendientes()->count(),
            'fallidos' => Pago::fallidos()->count(),
            'monto_total' => Pago::completados()->sum('monto'),
        ];

        return view('admin.pagos.index', compact('pagos', 'estadisticas'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pago = Pago::with(['reserva', 'cliente', 'intentos'])->findOrFail($id);
        
        return view('admin.pagos.show', compact('pago'));
    }

    /**
     * Mostrar intentos de pago
     */
    public function intentos()
    {
        $intentos = IntentoPago::with(['pago', 'reserva'])
            ->orderBy('fecha_intento', 'desc')
            ->paginate(30);

        return view('admin.pagos.intentos', compact('intentos'));
    }
}
