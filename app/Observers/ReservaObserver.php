<?php

namespace App\Observers;

use App\Jobs\BorrarPinAlVencer;
use App\Models\Reserva;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Observer de reservas para mantener coherencia entre el
 * estado en BD y el PIN programado en la cerradura.
 *
 * Dos casos cubiertos:
 *
 *  A) Cambio de fecha_salida (extension/acortamiento de estancia):
 *     - El Job BorrarPinAlVencer original quedo encolado con el delay
 *       de la fecha_salida vieja.
 *     - Si NO hacemos nada, el job se ejecutara antes de tiempo (acortar)
 *       y el huesped se quedara sin clave; o nunca se ejecutara a tiempo
 *       (extender) y el PIN quedara como zombie.
 *     - Solucion: cuando cambia fecha_salida y la reserva tiene
 *       ttlock_pin_id activo, dispatch un nuevo Job con el nuevo delay.
 *       El job viejo, al ejecutarse, validara la fecha_salida actual y
 *       saldra silenciosamente si aun no toca (logica ya en el Job).
 *
 *  B) Cancelacion de reserva (estado_id 4 o 9) tras programar PIN:
 *     - El PIN sigue activo en la cerradura hasta su invalid_time
 *       natural. El cliente cancelado podria seguir entrando al
 *       apartamento. Riesgo serio.
 *     - Solucion: al cambiar estado_id a 4/9 y existir ttlock_pin_id,
 *       hacer DELETE en Tuyalaravel inmediatamente.
 *     - El BorrarPinAlVencer encolado tambien tiene guard contra
 *       canceladas (sale silenciosamente al ejecutarse).
 *
 * NO envia ningun WhatsApp — solo gestiona el PIN en la cerradura.
 */
class ReservaObserver
{
    public function updated(Reserva $reserva): void
    {
        try {
            // A) Cambio de fecha_salida con PIN activo
            if ($reserva->wasChanged('fecha_salida') && !empty($reserva->ttlock_pin_id)) {
                $this->reencolarBorradoConNuevaFecha($reserva);
            }

            // B) Cambio de estado a cancelada con PIN activo
            if ($reserva->wasChanged('estado_id')
                && in_array($reserva->estado_id, [4, 9], true)
                && !empty($reserva->ttlock_pin_id)) {
                $this->revocarPinPorCancelacion($reserva);
            }
        } catch (\Throwable $e) {
            // Nunca romper el flujo de update por error en el observer
            Log::warning('[ReservaObserver] Excepcion en updated: ' . $e->getMessage(), [
                'reserva_id' => $reserva->id,
            ]);
        }
    }

    private function reencolarBorradoConNuevaFecha(Reserva $reserva): void
    {
        try {
            $cuando = \Carbon\Carbon::parse($reserva->fecha_salida)
                ->setTime(11, 0, 0)
                ->addMinutes(30);

            BorrarPinAlVencer::dispatch($reserva->id)->delay($cuando);

            Log::info('[ReservaObserver] Job de borrado reencolado por cambio fecha_salida', [
                'reserva_id' => $reserva->id,
                'nueva_fecha' => $reserva->fecha_salida,
                'cuando_borrar' => $cuando->toDateTimeString(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('[ReservaObserver] No se pudo reencolar Job: ' . $e->getMessage());
        }
    }

    private function revocarPinPorCancelacion(Reserva $reserva): void
    {
        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) {
            Log::warning('[ReservaObserver] Falta config Tuyalaravel, no se puede revocar PIN');
            return;
        }

        $reference = "reserva_{$reserva->id}";

        try {
            // Lookup id interno en Tuyalaravel
            $look = Http::withHeaders(['X-API-Key' => $key])->timeout(15)
                ->get(rtrim($url, '/') . "/api/pins/by-reference/" . rawurlencode($reference));

            if ($look->status() === 404) {
                $reserva->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                Log::info('[ReservaObserver] Reserva cancelada: PIN ya no estaba en Tuyalaravel', [
                    'reserva_id' => $reserva->id,
                ]);
                return;
            }
            if (!$look->successful()) {
                Log::warning('[ReservaObserver] Tuyalaravel HTTP ' . $look->status() . ' al consultar PIN cancelada');
                return;
            }

            $internalId = $look->json('data.id');
            $extRef = $look->json('data.external_reference');

            // Defensa: solo borrar si confirmamos que es de esta reserva
            if (!str_starts_with((string) $extRef, 'reserva_')) {
                Log::warning("[ReservaObserver] PIN no es de reserva (ext={$extRef}), no toco");
                return;
            }

            $del = Http::withHeaders(['X-API-Key' => $key])->timeout(20)
                ->delete(rtrim($url, '/') . "/api/pins/{$internalId}");

            if ($del->successful()) {
                $reserva->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                Log::info('[ReservaObserver] Reserva cancelada: PIN revocado de la cerradura', [
                    'reserva_id' => $reserva->id, 'internal_id' => $internalId,
                ]);

                // Encadenado: liberado un slot, programar la siguiente
                try {
                    $apt = $reserva->apartamento;
                    $lockId = $apt?->tuyalaravel_lock_id ?? $apt?->ttlock_lock_id;
                    if ($lockId) {
                        app(\App\Services\CerraduraSlotManager::class)
                            ->programarSiguientesDelLock((int) $lockId, 1);
                    }
                } catch (\Throwable $e) {
                    Log::warning('[ReservaObserver] No se pudo programar siguiente: ' . $e->getMessage());
                }
            } else {
                Log::warning('[ReservaObserver] DELETE devolvio HTTP ' . $del->status());
            }
        } catch (\Throwable $e) {
            Log::warning('[ReservaObserver] Excepcion revocando: ' . $e->getMessage());
        }
    }
}
