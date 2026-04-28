<?php

namespace App\Jobs;

use App\Models\Reserva;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Job que se programa con `delay($invalidTime + N min)` cuando
 * se crea un PIN. A la hora exacta de vencimiento llama a
 * DELETE /api/pins/by-reference/reserva_{id} y libera el slot en la
 * cerradura.
 *
 * Razon: la cerradura del portal del edificio Hawkins Suites tiene un
 * limite fisico de PINs activos simultaneos (~9-10). Cuando el PIN expira
 * por `invalid_time`, Tuya lo marca como expired pero NO libera el slot
 * hasta que se llama DELETE explicitamente. Sin este job, los slots
 * acumulaban PINs zombies y bloqueaban la programacion de nuevas reservas.
 *
 * Defensa en profundidad: hay tambien un cron `cerraduras:purgar-zombies`
 * que recorre semanalmente las reservas con fecha_salida pasada por si
 * algun job se perdio (Redis flush, queue worker caido, etc).
 *
 * Seguridad:
 *  - Solo borra PINs cuya `external_reference` empieza por `reserva_` (no
 *    toca PINs permanentes de limpiadora/seguridad que tienen otro patron).
 *  - Si Tuyalaravel no responde, falla el job y se reintenta segun la
 *    politica de la queue. No se queda corrupto.
 */
class BorrarPinAlVencer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Numero de reintentos si falla (cada uno con backoff exponencial) */
    public int $tries = 3;

    /** Si la queue esta caida, mantener el job en BD hasta que vuelva */
    public int $timeout = 60;

    public function __construct(public int $reservaId)
    {
    }

    public function handle(): void
    {
        $reserva = Reserva::find($this->reservaId);
        if (!$reserva) {
            Log::info('[BorrarPinAlVencer] Reserva no existe ya, salto', [
                'reserva_id' => $this->reservaId,
            ]);
            return;
        }

        // Si la reserva fue cancelada o el PIN ya fue limpiado por otro
        // proceso, no hacer nada.
        if (in_array($reserva->estado_id, [4, 9], true)) {
            Log::info('[BorrarPinAlVencer] Reserva cancelada, salto', [
                'reserva_id' => $reserva->id, 'estado_id' => $reserva->estado_id,
            ]);
            return;
        }

        if (empty($reserva->ttlock_pin_id)) {
            Log::info('[BorrarPinAlVencer] Reserva ya sin pin, salto', [
                'reserva_id' => $reserva->id,
            ]);
            return;
        }

        // Defensa: solo borrar si invalid_time ya pasó. Si pasamos
        // delay($invalidTime+1min) deberia ser cierto siempre, pero por
        // si Laravel reintenta antes de tiempo lo verificamos.
        $salida = \Carbon\Carbon::parse($reserva->fecha_salida)->setTime(11, 0, 0);
        if (now()->lt($salida)) {
            Log::info('[BorrarPinAlVencer] Reserva aun activa, no purgo', [
                'reserva_id' => $reserva->id, 'salida' => $salida->toDateTimeString(),
            ]);
            return;
        }

        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) {
            Log::warning('[BorrarPinAlVencer] Falta TUYA_APP_URL/key, salto');
            return;
        }

        $reference = "reserva_{$reserva->id}";

        try {
            // 1) Lookup el id interno del PIN en Tuyalaravel
            $resp = Http::withHeaders(['X-API-Key' => $key])->timeout(15)
                ->get(rtrim($url, '/') . "/api/pins/by-reference/" . rawurlencode($reference));

            if ($resp->status() === 404) {
                // Ya no existe. Limpiamos en nuestra BD. El slot fisico ya
                // esta libre (alguien lo borro por nosotros), asi que tambien
                // intentamos programar la siguiente reserva pendiente del lock.
                $reserva->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                Log::info('[BorrarPinAlVencer] PIN ya no existe en Tuyalaravel, BD limpia', [
                    'reserva_id' => $reserva->id,
                ]);
                $this->programarSiguienteDelLock($reserva);
                return;
            }
            if (!$resp->successful()) {
                throw new \RuntimeException("HTTP {$resp->status()} buscando PIN");
            }

            $internalId = $resp->json('data.id');
            if (!$internalId) {
                throw new \RuntimeException('Tuyalaravel devolvio sin id');
            }

            // 2) DELETE — Tuyalaravel hace lockService->deleteCode(),
            //    que borra del cloud Y de la cerradura fisica.
            $del = Http::withHeaders(['X-API-Key' => $key])->timeout(20)
                ->delete(rtrim($url, '/') . "/api/pins/{$internalId}");

            if ($del->successful()) {
                $reserva->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                Log::info('[BorrarPinAlVencer] PIN borrado correctamente', [
                    'reserva_id' => $reserva->id,
                    'internal_id' => $internalId,
                    'response' => mb_substr((string) $del->body(), 0, 200),
                ]);

                // [2026-04-28] Encadenado: liberado un slot, programar la
                // siguiente reserva pendiente del mismo lock.
                $this->programarSiguienteDelLock($reserva);
                return;
            }

            // Si DELETE devuelve 5xx → reintentar despues. La queue lo manejara.
            throw new \RuntimeException("DELETE devolvio HTTP {$del->status()}: " . mb_substr($del->body(), 0, 200));
        } catch (\Throwable $e) {
            Log::warning('[BorrarPinAlVencer] Excepcion: ' . $e->getMessage(), [
                'reserva_id' => $reserva->id,
            ]);
            throw $e; // dejar que la queue reintente
        }
    }

    /**
     * Helper: tras liberar un slot (sea por DELETE OK o por verificar 404),
     * programa la siguiente reserva diferida del mismo lock. NO envia
     * WhatsApp — solo programa el PIN en cerradura. Cualquier excepcion
     * se loguea pero no rompe el flujo del Job.
     */
    private function programarSiguienteDelLock(Reserva $reserva): void
    {
        try {
            $apt = $reserva->apartamento;
            $lockId = $apt?->tuyalaravel_lock_id ?? $apt?->ttlock_lock_id;
            if (!$lockId) return;
            app(\App\Services\CerraduraSlotManager::class)
                ->programarSiguientesDelLock((int) $lockId, 1);
        } catch (\Throwable $e) {
            Log::warning('[BorrarPinAlVencer] No se pudo programar siguiente: ' . $e->getMessage());
        }
    }
}
