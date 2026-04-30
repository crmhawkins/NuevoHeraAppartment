<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Services\MIRService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-22] Reintentar automaticamente el envio a MIR de las reservas
 * que quedaron en 'error_validacion'.
 *
 * Motivacion: hoy cuando el admin arregla un campo en el panel de revision
 * manual (o cuando el cliente sube el DNI con mejores datos) la reserva
 * queda ahi hasta que alguien pulsa "Revalidar" a mano. Si el admin no
 * entra al panel, la reserva puede llegar al dia de entrada sin enviarse.
 *
 * Este comando recoge todas las reservas en 'error_validacion' y las pasa
 * por MIRService::enviarSiLista() de nuevo. Si la validacion ahora pasa,
 * se envian solas. Si sigue habiendo errores, vuelven a quedarse ahi con
 * el mir_respuesta actualizado (los issues viejos desaparecen, los nuevos
 * se anaden).
 *
 * Se ejecuta cada 10 min desde Kernel.php. Es idempotente: si no hay
 * cambios respecto al ultimo intento, simplemente reescribe mir_respuesta
 * con los mismos issues.
 */
class MirReintentarRevalidacion extends Command
{
    protected $signature = 'mir:reintentar-revalidacion {--limit=50 : maximo de reservas por ejecucion} {--dry-run : solo listar, no ejecutar}';
    protected $description = 'Reintenta el envio a MIR de reservas en error_validacion. Se ejecuta cada 10 min en cron.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $dryRun = (bool) $this->option('dry-run');

        // [2026-04-26] Ampliado para cubrir 2 estados:
        //  - error_validacion: la falla la deteccion el validador interno
        //  - error: el MIR rechazo el XML (XSD/contenido) — antes se quedaba
        //    fuera de este cron y solo el admin podia reintentar a mano. Ahora
        //    el `mir:auto-rescate` (cada 30 min) corrige los datos y deja la
        //    reserva en error_validacion o estado=null, asi que reintentar
        //    aqui las recoge y las envia.
        // Tambien permitimos rechazado (estado del SOAP cuando MIR rechaza
        // contenido pero acepta el formato).
        $query = Reserva::query()
            ->whereIn('mir_estado', ['error_validacion', 'error', 'rechazado'])
            ->where('estado_id', '!=', 4)
            ->where('dni_entregado', true)
            ->whereDate('fecha_entrada', '>=', now()->subDays(7)->toDateString())
            ->orderBy('fecha_entrada')
            ->limit($limit);

        $reservas = $query->get();

        if ($reservas->isEmpty()) {
            $this->info('No hay reservas pendientes de revalidacion.');
            return self::SUCCESS;
        }

        $this->info("Revalidando {$reservas->count()} reservas...");

        $enviadas = 0;
        $sigueBloqueadas = 0;
        $errores = 0;

        foreach ($reservas as $r) {
            if ($dryRun) {
                $this->line(" [DRY] Reserva #{$r->id} · entrada {$r->fecha_entrada}");
                continue;
            }

            try {
                $mir = new MIRService();
                $resultado = $mir->enviarSiLista($r);
                $r->refresh();

                if ($resultado !== null && !empty($resultado['success'])) {
                    $enviadas++;
                    $this->info(" ✓ Reserva #{$r->id} enviada a MIR. Lote: " . ($resultado['codigo_referencia'] ?? '-'));
                } elseif (in_array($r->mir_estado, ['error_validacion', 'error', 'rechazado'], true)) {
                    $sigueBloqueadas++;
                    $this->line(" · Reserva #{$r->id} sigue bloqueada (estado={$r->mir_estado})");
                } elseif ($r->mir_estado === 'enviado') {
                    // Ya se envio aunque $resultado sea null (edge case)
                    $enviadas++;
                    $this->info(" ✓ Reserva #{$r->id} ya marcada como enviada");
                } else {
                    $errores++;
                    $this->warn(" ! Reserva #{$r->id}: estado {$r->mir_estado} — " . ($resultado['mensaje'] ?? 'sin detalle'));
                }
            } catch (\Throwable $e) {
                $errores++;
                Log::error('[mir:reintentar-revalidacion] Excepcion', [
                    'reserva_id' => $r->id, 'error' => $e->getMessage(),
                ]);
                $this->error(" ✗ Reserva #{$r->id}: " . mb_substr($e->getMessage(), 0, 100));
            }
        }

        $this->line('');
        $this->info("Resumen: enviadas={$enviadas}, bloqueadas={$sigueBloqueadas}, errores={$errores}");

        if ($enviadas > 0 || $errores > 0) {
            Log::info('[mir:reintentar-revalidacion] Resultados', [
                'total'       => $reservas->count(),
                'enviadas'    => $enviadas,
                'bloqueadas'  => $sigueBloqueadas,
                'errores'     => $errores,
            ]);
        }

        return self::SUCCESS;
    }
}
