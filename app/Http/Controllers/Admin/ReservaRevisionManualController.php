<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Services\MIRService;
use App\Services\MirPreflightValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-20] Panel de reservas que requieren revision manual por fallo
 * del preflight de MIR.
 *
 * Una reserva llega aqui cuando:
 *  - El cliente ya subio el DNI (dni_entregado=1).
 *  - MIRService::enviarSiLista() intento enviar pero el preflight detecto
 *    errores bloqueantes (CP incorrecto, DNI sin letra, etc.).
 *  - mir_estado quedo en 'error_validacion' y mir_respuesta contiene el
 *    JSON de issues detectados.
 *
 * Desde aqui el admin ve cada issue y puede:
 *  - Ir directamente a editar el cliente o el huesped afectado.
 *  - Re-validar la reserva una vez corregida (boton que llama a
 *    enviarSiLista de nuevo).
 */
class ReservaRevisionManualController extends Controller
{
    public function index(Request $request)
    {
        $reservas = Reserva::with(['cliente', 'apartamento.edificio'])
            ->where('mir_estado', 'error_validacion')
            ->where('estado_id', '!=', 4) // no canceladas
            ->where('dni_entregado', true)
            ->orderBy('fecha_entrada')
            ->get()
            ->map(function ($r) {
                $r->_issues_parsed = $this->parseIssues($r->mir_respuesta);
                return $r;
            });

        return view('admin.reservas-revision-manual.index', compact('reservas'));
    }

    public function revalidar(Request $request, int $id)
    {
        $reserva = Reserva::findOrFail($id);

        try {
            $mirService = new MIRService();
            $resultado = $mirService->enviarSiLista($reserva);
            $reserva->refresh();

            if ($resultado === null) {
                // Sigue bloqueada por validacion o datos no listos
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('warning', "Reserva #{$reserva->id}: sigue bloqueada. Estado: " . ($reserva->mir_estado ?: 'desconocido'));
            }

            if (!empty($resultado['success'])) {
                return redirect()
                    ->route('admin.reservas-revision-manual.index')
                    ->with('success', "Reserva #{$reserva->id}: enviada a MIR correctamente. Lote: " . ($resultado['codigo_referencia'] ?? '-'));
            }

            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('error', "Reserva #{$reserva->id}: " . ($resultado['mensaje'] ?? 'MIR rechazo el envio'));
        } catch (\Throwable $e) {
            Log::error('[RevisionManual] Error revalidando', ['reserva_id' => $id, 'error' => $e->getMessage()]);
            return redirect()
                ->route('admin.reservas-revision-manual.index')
                ->with('error', "Excepcion al revalidar #{$id}: " . $e->getMessage());
        }
    }

    /**
     * Ignora una reserva: la saca del listado cambiando mir_estado a
     * 'ignorado_manual'. Util cuando el admin decide que no se va a
     * enviar a MIR (p.ej. reserva cancelada de facto, huesped no va a
     * venir, etc.).
     */
    public function ignorar(Request $request, int $id)
    {
        $reserva = Reserva::findOrFail($id);
        $reserva->mir_estado = 'ignorado_manual';
        $reserva->save();

        return redirect()
            ->route('admin.reservas-revision-manual.index')
            ->with('success', "Reserva #{$id} marcada como ignorada (no se enviara a MIR).");
    }

    /**
     * Parsea el JSON de mir_respuesta para extraer los issues detectados
     * con el formato [['severity','campo','mensaje','entidad','entidad_id']].
     */
    private function parseIssues(?string $raw): array
    {
        if (empty($raw)) return [];
        try {
            $data = json_decode($raw, true);
            if (!is_array($data)) return [];
            $issues = $data['issues'] ?? [];
            if (!is_array($issues)) return [];
            // Deduplicar por campo+mensaje
            $seen = [];
            $out = [];
            foreach ($issues as $i) {
                $key = ($i['entidad'] ?? '') . '|' . ($i['campo'] ?? '') . '|' . ($i['mensaje'] ?? '');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                $out[] = $i;
            }
            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }
}
