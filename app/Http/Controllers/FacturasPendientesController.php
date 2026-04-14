<?php

namespace App\Http\Controllers;

use App\Models\FacturaPendiente;
use App\Models\Gastos;
use App\Services\FacturaScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FacturasPendientesController extends Controller
{
    public function index(Request $request)
    {
        // Tres pestanas: procesando/pendiente (en cola), espera (sin match aun), error.
        $enCola  = FacturaPendiente::whereIn('status', ['pendiente', 'procesando'])
            ->orderByDesc('id')
            ->get();

        $enEspera = FacturaPendiente::where('status', 'espera')
            ->orderByDesc('id')
            ->get();

        $conError = FacturaPendiente::where('status', 'error')
            ->orderByDesc('id')
            ->get();

        $asociadasRecientes = FacturaPendiente::where('status', 'asociada')
            ->orderByDesc('resolved_at')
            ->limit(20)
            ->get();

        return view('admin.facturas_pendientes.index', [
            'enCola' => $enCola,
            'enEspera' => $enEspera,
            'conError' => $conError,
            'asociadasRecientes' => $asociadasRecientes,
        ]);
    }

    /**
     * Devuelve el archivo de imagen (sirve thumbnails/preview).
     */
    public function imagen(int $id)
    {
        $fp = FacturaPendiente::findOrFail($id);
        $abs = storage_path('app/' . ltrim($fp->storage_path, '/'));
        if (!file_exists($abs)) {
            abort(404);
        }
        return response()->file($abs, [
            'Content-Type' => $fp->mime_type ?: 'application/octet-stream',
        ]);
    }

    /**
     * Asocia manualmente una factura a un gasto elegido por el admin.
     */
    public function asociarManual(Request $request, int $id)
    {
        $request->validate([
            'gasto_id' => ['required', 'integer', 'exists:gastos,id'],
        ]);

        $fp = FacturaPendiente::findOrFail($id);
        $gasto = Gastos::findOrFail($request->input('gasto_id'));

        try {
            app(FacturaScannerService::class)->asociar($fp, $gasto);
        } catch (\Throwable $e) {
            Log::error('[FacturasPendientes] Error asociando manual', [
                'id' => $id, 'gasto_id' => $gasto->id, 'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['msg' => 'Error al asociar: ' . $e->getMessage()]);
        }

        return back()->with('success', 'Factura asociada al gasto #' . $gasto->id);
    }

    /**
     * Reenvia una factura al procesamiento (para errores transitorios de IA).
     */
    public function reintentar(int $id)
    {
        $fp = FacturaPendiente::findOrFail($id);
        $fp->update([
            'status' => 'pendiente',
            'error_message' => null,
        ]);
        return back()->with('success', 'Factura marcada para reintentar en el proximo ciclo (5 min).');
    }

    /**
     * Descarta una factura (mueve a error/descartadas/).
     */
    public function descartar(int $id)
    {
        $fp = FacturaPendiente::findOrFail($id);
        $srcAbs = storage_path('app/' . ltrim($fp->storage_path, '/'));
        if (file_exists($srcAbs)) {
            $destDir = storage_path('app/facturas/error/descartadas');
            if (!is_dir($destDir)) @mkdir($destDir, 0755, true);
            $destAbs = $destDir . DIRECTORY_SEPARATOR . basename($fp->storage_path);
            @rename($srcAbs, $destAbs);
            $fp->storage_path = 'facturas/error/descartadas/' . basename($fp->storage_path);
        }
        $fp->status = 'error';
        $fp->error_message = ($fp->error_message ? $fp->error_message . ' | ' : '') . 'descartada por el admin';
        $fp->resolved_at = now();
        $fp->save();
        return back()->with('success', 'Factura descartada.');
    }
}
