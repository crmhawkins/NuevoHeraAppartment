<?php

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Pre-flight de slots de la cerradura del portal.
 *
 * Razon: la cerradura del portal del edificio Hawkins Suites tiene un
 * limite fisico de PINs activos (suele ser 9-10). Cuando se intenta
 * programar una reserva mas y los slots estan llenos, Tuya rechaza
 * (silenciosamente o no) y el huesped se queda sin clave.
 *
 * Este servicio se llama ANTES de programar un PIN nuevo:
 *  - Cuenta cuantos PINs hay activos en el lock.
 *  - Si la cuenta indica saturacion, ejecuta una purga puntual de los
 *    PINs de reservas ya finalizadas (huespedes que ya salieron).
 *  - Devuelve true si tras la purga hay slot libre, false si la
 *    cerradura esta saturada por reservas legitimas concurrentes.
 *
 * SEGURIDAD CRITICA:
 *  - Solo borra PINs cuya `external_reference` empieza por `reserva_`
 *    (NO toca PINs permanentes de limpiadora/seguridad).
 *  - Solo borra PINs cuya reserva tiene fecha_salida < ahora.
 *  - Llama a Tuyalaravel solo via /api/pins/by-reference (endpoint
 *    seguro, no rompe nada existente).
 *
 * El AccessCodeService llama a este servicio dentro de try/catch para
 * NO ROMPER el flujo principal si algo falla aqui.
 */
class CerraduraSlotManager
{
    /** Slot maximo "seguro" antes de empezar a purgar (limite real ~9-10). */
    public const SLOTS_LIMITE = 9;

    /**
     * Comprueba que hay slot libre en el lock. Si no, intenta liberarlo
     * purgando PINs de reservas finalizadas. Devuelve true si hay slot
     * disponible (aunque sea tras purga), false si la cerradura esta
     * saturada y no se pudo liberar nada.
     */
    public function asegurarSlotLibre(int $lockId): bool
    {
        try {
            // 1. Listar reservas con PIN activo en este lock
            $reservasConPin = Reserva::whereNotNull('ttlock_pin_id')
                ->where('codigo_enviado_cerradura', 1)
                ->whereDate('fecha_salida', '>=', now()->subDay()->toDateString())
                ->whereNotIn('estado_id', [4, 9])
                ->whereHas('apartamento', function ($q) use ($lockId) {
                    $q->where('tuyalaravel_lock_id', $lockId)
                      ->orWhere('ttlock_lock_id', $lockId);
                })
                ->count();

            if ($reservasConPin < self::SLOTS_LIMITE) {
                return true; // hay sitio
            }

            Log::warning('[SlotManager] Lock saturado, intentando purgar', [
                'lock_id' => $lockId, 'pins_activos' => $reservasConPin,
            ]);

            // 2. Purgar PINs de reservas ya finalizadas (salida pasada)
            $candidatas = Reserva::whereNotNull('ttlock_pin_id')
                ->whereDate('fecha_salida', '<', now()->toDateString())
                ->whereHas('apartamento', function ($q) use ($lockId) {
                    $q->where('tuyalaravel_lock_id', $lockId)
                      ->orWhere('ttlock_lock_id', $lockId);
                })
                ->get();

            $borrados = 0;
            foreach ($candidatas as $r) {
                if ($this->borrarPinDeReserva($r)) {
                    $borrados++;
                }
            }

            Log::info('[SlotManager] Purga puntual', [
                'lock_id' => $lockId, 'borrados' => $borrados,
            ]);

            // 3. Reconfirmar tras purga
            $reservasConPin = Reserva::whereNotNull('ttlock_pin_id')
                ->where('codigo_enviado_cerradura', 1)
                ->whereDate('fecha_salida', '>=', now()->subDay()->toDateString())
                ->whereNotIn('estado_id', [4, 9])
                ->whereHas('apartamento', function ($q) use ($lockId) {
                    $q->where('tuyalaravel_lock_id', $lockId)
                      ->orWhere('ttlock_lock_id', $lockId);
                })
                ->count();

            return $reservasConPin < self::SLOTS_LIMITE;
        } catch (\Throwable $e) {
            // No bloquear el flujo principal por error en pre-flight
            Log::warning('[SlotManager] Excepcion en asegurarSlotLibre, devolviendo true por seguridad: ' . $e->getMessage());
            return true;
        }
    }

    /**
     * Borra el PIN de una reserva via Tuyalaravel. Solo si su
     * external_reference empieza por "reserva_" (excluye permanentes).
     */
    private function borrarPinDeReserva(Reserva $r): bool
    {
        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) return false;

        $ref = "reserva_{$r->id}";

        try {
            // Lookup del id interno
            $look = Http::withHeaders(['X-API-Key' => $key])->timeout(15)
                ->get(rtrim($url, '/') . "/api/pins/by-reference/" . rawurlencode($ref));

            if ($look->status() === 404) {
                // Ya no existe en Tuyalaravel, solo limpiar BD
                $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                return true;
            }
            if (!$look->successful()) {
                return false;
            }

            $internalId = $look->json('data.id');
            $extRef = $look->json('data.external_reference');

            // Defensa: confirmar que la external_reference empieza por reserva_
            if (!str_starts_with((string) $extRef, 'reserva_')) {
                Log::warning("[SlotManager] PIN id={$internalId} no es de reserva (ext={$extRef}), salto");
                return false;
            }

            $del = Http::withHeaders(['X-API-Key' => $key])->timeout(20)
                ->delete(rtrim($url, '/') . "/api/pins/{$internalId}");
            if ($del->successful()) {
                $r->update(['ttlock_pin_id' => null, 'codigo_enviado_cerradura' => 0]);
                Log::info("[SlotManager] PIN purgado reserva={$r->id} internal_id={$internalId}");
                return true;
            }
        } catch (\Throwable $e) {
            Log::warning("[SlotManager] Error borrando reserva={$r->id}: " . $e->getMessage());
        }
        return false;
    }
}
