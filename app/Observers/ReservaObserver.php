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

    /**
     * [2026-05-05] Hook al CREAR una reserva nueva. Si la reserva entra HOY
     * y son despues de la rotacion-diaria (>= 11:05) o despues del cron de
     * claves (>= 14:00), programar+enviar inmediatamente sin esperar al
     * proximo cron horario. Esto cubre reservas de ultima hora (Booking
     * de noche, walk-in, etc.) que de otra forma se quedarian sin PIN
     * hasta el siguiente tick del cron.
     *
     * NO se ejecuta si la reserva entra manana o despues — esas se programan
     * solas el dia de su entrada (regla §0.2: solo programar el dia mismo).
     */
    public function created(Reserva $reserva): void
    {
        try {
            if (!$reserva->fecha_entrada) return;

            $fechaEntrada = \Carbon\Carbon::parse($reserva->fecha_entrada);
            $hoy = \Carbon\Carbon::today();
            $ahora = \Carbon\Carbon::now();

            // Solo procesar si la reserva entra HOY mismo
            if (!$fechaEntrada->isSameDay($hoy)) {
                return;
            }

            // Si esta cancelada al crearse (raro pero posible), no hacer nada
            if (in_array($reserva->estado_id, [4, 9], true)) {
                return;
            }

            Log::info('[ReservaObserver] Reserva nueva con entrada HOY — programar inmediatamente', [
                'reserva_id' => $reserva->id,
                'hora' => $ahora->format('H:i'),
            ]);

            // Programar PIN ahora si el cron de rotacion ya paso (11:05+)
            // o si simplemente queremos garantizar que esta listo cuanto antes.
            if ($ahora->format('H:i') >= '11:00') {
                try {
                    app(\App\Services\AccessCodeService::class)->generarYProgramar($reserva);
                    $reserva->refresh();
                    Log::info('[ReservaObserver] PIN programado inmediatamente para reserva nueva HOY', [
                        'reserva_id' => $reserva->id,
                        'codigo_enviado_cerradura' => $reserva->codigo_enviado_cerradura,
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('[ReservaObserver] Error programando PIN de reserva nueva HOY', [
                        'reserva_id' => $reserva->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            // (Si son antes de las 11, se programara con la rotacion-diaria
            // a las 11:05, no hace falta hacer nada aqui)

        } catch (\Throwable $e) {
            Log::warning('[ReservaObserver] Excepcion en created: ' . $e->getMessage(), [
                'reserva_id' => $reserva->id ?? null,
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
