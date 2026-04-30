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
    /**
     * Slot maximo "seguro" antes de empezar a purgar.
     *
     * [2026-04-28 FIX #3] Bajado de 9 a 6 para dejar colchon. Razon:
     * el conteo solo mira reservas (codigo_enviado_cerradura=1), pero
     * la cerradura fisica tambien tiene PINs permanentes (limpiadora,
     * seguridad) y posibles zombies historicos no contabilizados.
     * El limite fisico real es ~9-10 PINs activos. Usar 6 evita que
     * el conteo subestime y nos lleve a saturacion silenciosa.
     */
    public const SLOTS_LIMITE = 6;

    /**
     * Comprueba que hay slot libre en el lock. Si no, intenta liberarlo
     * purgando PINs de reservas finalizadas. Devuelve true si hay slot
     * disponible (aunque sea tras purga), false si la cerradura esta
     * saturada y no se pudo liberar nada.
     */
    public function asegurarSlotLibre(int $lockId): bool
    {
        try {
            // [2026-04-28] Conteo REAL desde Tuyalaravel (preferido). Si falla
            // o no responde, caemos al conteo por BD del CRM.
            $reservasConPin = $this->contarPinsRealEnLock($lockId);
            if ($reservasConPin === null) {
                $reservasConPin = Reserva::whereNotNull('ttlock_pin_id')
                    ->where('codigo_enviado_cerradura', 1)
                    ->whereDate('fecha_salida', '>=', now()->subDay()->toDateString())
                    ->whereNotIn('estado_id', [4, 9])
                    ->whereHas('apartamento', function ($q) use ($lockId) {
                        $q->where('tuyalaravel_lock_id', $lockId)
                          ->orWhere('ttlock_lock_id', $lockId);
                    })
                    ->count();
                Log::info("[SlotManager] Conteo Tuyalaravel fallido, usando BD: {$reservasConPin}");
            }

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

            // Encadenado: por cada slot liberado, programar la siguiente
            // reserva pendiente del mismo lock que ya tiene PIN generado
            // pero aun no esta en la cerradura.
            if ($borrados > 0) {
                $this->programarSiguientesDelLock($lockId, $borrados);
            }

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
     * [2026-04-28] Programa el PIN de la(s) siguiente(s) reserva(s)
     * pendientes del mismo lock cuando se libera un slot.
     *
     * Caso de uso: huesped sale -> Job borra su PIN -> slot libre ->
     * el huesped que viene en X dias y aun no tiene PIN programado en
     * la cerradura (porque cuando se creo la reserva la entrada estaba
     * fuera de la ventana de Tuya/TTLock) ahora SI puede programarse.
     *
     * Filtros importantes:
     *  - codigo_acceso ya generado (no nuevo PIN, solo enviar a cerradura).
     *  - codigo_enviado_cerradura=0 (aun no programado).
     *  - fecha_entrada en ventana del proveedor (Tuya 7d, TTLock 150d).
     *  - estado_id no canceladas.
     *
     * NO envia WhatsApp al huesped — eso lo hace el cron clavesAutomatico
     * cuando se acerca su entrada. Aqui solo se programa el PIN en la
     * cerradura.
     *
     * @param int $lockId ID del lock (puede ser tuyalaravel_lock_id o ttlock_lock_id)
     * @param int $cuantos Cuantas reservas programar (1 por defecto)
     * @return int Numero efectivamente programadas
     */
    public function programarSiguientesDelLock(int $lockId, int $cuantos = 1): int
    {
        if ($cuantos < 1) return 0;

        try {
            $hoy = now()->toDateString();
            $limiteTuya = now()->addDays(7)->toDateString();
            $limiteTTLock = now()->addDays(150)->toDateString();

            // Buscar reservas del lock con PIN generado pero NO enviado a cerradura
            $candidatas = Reserva::with('apartamento')
                ->whereNotNull('codigo_acceso')
                ->where('codigo_enviado_cerradura', 0)
                ->whereNotIn('estado_id', [4, 9])
                ->where('fecha_entrada', '>=', $hoy)
                ->whereHas('apartamento', function ($q) use ($lockId) {
                    $q->where('tuyalaravel_lock_id', $lockId)
                      ->orWhere('ttlock_lock_id', $lockId);
                })
                ->orderBy('fecha_entrada')
                ->limit($cuantos * 3) // pedir mas para filtrar luego por ventana
                ->get();

            // Filtrar por ventana del proveedor (Tuya 7d, TTLock 150d)
            $candidatas = $candidatas->filter(function ($r) use ($limiteTuya, $limiteTTLock) {
                $apt = $r->apartamento;
                if (!$apt) return false;
                $tipo = strtolower($apt->tipo_cerradura ?? '');
                $fechaE = substr((string) $r->fecha_entrada, 0, 10);
                if ($tipo === 'tuya' && $fechaE <= $limiteTuya) return true;
                if ($tipo === 'ttlock' && $fechaE <= $limiteTTLock) return true;
                return false;
            })->values()->take($cuantos);

            if ($candidatas->isEmpty()) {
                Log::info("[SlotManager] No hay siguientes reservas que programar en lock {$lockId}");
                return 0;
            }

            $accessCodeSvc = app(AccessCodeService::class);
            $programadas = 0;
            foreach ($candidatas as $r) {
                try {
                    $ok = $accessCodeSvc->reintentarOFallback($r);
                    if ($ok) {
                        Log::info("[SlotManager] Programada siguiente reserva {$r->id} en lock {$lockId}", [
                            'fecha_entrada' => $r->fecha_entrada,
                        ]);
                        $programadas++;

                        // [2026-04-28 FIX #2] Tras programar exitoso, encolar
                        // el Job de borrado al vencer. Si no, este PIN
                        // recien programado quedaria como zombie al expirar
                        // (reintentarOFallback no encola el job por si solo).
                        try {
                            $r->refresh();
                            if (!empty($r->ttlock_pin_id)) {
                                $cuando = \Carbon\Carbon::parse($r->fecha_salida)
                                    ->setTime(11, 0, 0)
                                    ->addMinutes(30);
                                \App\Jobs\BorrarPinAlVencer::dispatch($r->id)->delay($cuando);
                                Log::info("[SlotManager] Job borrar al vencer encolado para reserva {$r->id} a las {$cuando}");
                            }
                        } catch (\Throwable $e) {
                            Log::warning('[SlotManager] No se pudo encolar Job de borrado: ' . $e->getMessage());
                        }
                    } else {
                        Log::warning("[SlotManager] No se pudo programar reserva {$r->id} en lock {$lockId} — el cron diario lo reintentara");
                    }
                } catch (\Throwable $e) {
                    Log::warning("[SlotManager] Excepcion programando reserva {$r->id}: " . $e->getMessage());
                }
            }
            return $programadas;
        } catch (\Throwable $e) {
            Log::warning('[SlotManager] Excepcion en programarSiguientesDelLock: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * [2026-04-28] Pregunta a Tuyalaravel cuantos PINs activos AHORA hay
     * en el lock. Devuelve null si Tuyalaravel no responde (caller debera
     * caer al conteo por BD).
     */
    private function contarPinsRealEnLock(int $lockId): ?int
    {
        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) return null;

        try {
            $resp = Http::withHeaders(['X-API-Key' => $key])->timeout(8)
                ->get(rtrim($url, '/') . "/api/locks/{$lockId}/pins-count");
            if (!$resp->successful()) return null;
            $active = $resp->json('data.active_now');
            return is_int($active) ? $active : null;
        } catch (\Throwable $e) {
            return null;
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
