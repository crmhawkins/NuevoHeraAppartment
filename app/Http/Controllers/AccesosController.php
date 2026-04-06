<?php
namespace App\Http\Controllers;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccesosController extends Controller
{
    public function index(Request $request)
    {
        $query = Reserva::with(['cliente', 'apartamento'])
            ->where('estado_id', '!=', 4);

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('codigo_reserva', 'like', "%{$search}%")
                  ->orWhereHas('cliente', fn($c) => $c->where('alias', 'like', "%{$search}%")
                                                        ->orWhere('nombre', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('estado_codigo')) {
            switch ($request->estado_codigo) {
                case 'sin_codigo':
                    $query->whereNull('codigo_acceso');
                    break;
                case 'sin_cerradura':
                    $query->whereNotNull('codigo_acceso')->where('codigo_enviado_cerradura', 0);
                    break;
                case 'ok':
                    $query->where('codigo_enviado_cerradura', 1);
                    break;
                case 'sin_datos':
                    $query->where('dni_entregado', 0);
                    break;
            }
        }

        // Por defecto mostrar reservas actuales y futuras
        if (!$request->filled('mostrar_pasadas')) {
            $query->where('fecha_salida', '>=', Carbon::now()->subDays(7)->toDateString());
        }

        $reservas = $query->orderBy('fecha_entrada', 'asc')->paginate(30)->withQueryString();

        return view('accesos.index', compact('reservas'));
    }

    public function regenerarCodigo(Request $request, int $reservaId)
    {
        $reserva = Reserva::with('apartamento')->findOrFail($reservaId);

        try {
            $codigo = DB::transaction(function () use ($reserva) {
                // Revocar el anterior si existe
                if ($reserva->ttlock_pin_id) {
                    app(\App\Services\AccessCodeService::class)->revocarPin($reserva);
                }

                return app(\App\Services\AccessCodeService::class)->generarYProgramar($reserva);
            });

            return response()->json([
                'success'       => true,
                'codigo_acceso' => $codigo,
                'mensaje'       => 'Código regenerado correctamente.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al regenerar código de acceso', [
                'reserva_id' => $reservaId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'mensaje' => 'Error al regenerar el código de acceso.',
            ], 500);
        }
    }
}
