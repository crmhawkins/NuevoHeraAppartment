<?php

namespace App\Services;

use App\Models\Reserva;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-28] Healthcheck de PINs programados en Tuyalaravel.
 *
 * Razon de existir: el 28/04/2026 el huesped Cesar Fernandez (reserva #6418)
 * no pudo entrar al portal Hawkins Suites. La causa: el PIN se programo el
 * dia anterior (Tuyalaravel respondio HTTP 200 + provider_code_id), pero
 * cuando el huesped intento entrar el PIN ya no estaba en la cerradura.
 * Tuya tiene un limite de slots y los PINs se desplazan silenciosamente.
 *
 * Este servicio comprueba contra Tuyalaravel que un PIN dado sigue activo:
 *  - GET /api/pins/{id}
 *  - Si HTTP 404 -> PIN se perdio
 *  - Si HTTP 200 + is_active=false -> expirado/revocado
 *  - Si HTTP 200 + is_active=true -> OK
 *
 * NO envia nada al huesped por si solo. La logica de "que hacer" cuando un
 * PIN esta perdido (reprogramar / fallback de emergencia) vive en el comando
 * cerraduras:healthcheck-pins, que orquesta usando este servicio.
 */
class CerraduraPinHealthService
{
    public const STATE_OK = 'ok';
    public const STATE_LOST = 'lost';      // 404, no existe en Tuyalaravel
    public const STATE_INACTIVE = 'inactive'; // existe pero is_active=false
    public const STATE_UNKNOWN = 'unknown';   // error HTTP / Tuyalaravel caido

    /**
     * Comprueba el estado del PIN de UNA reserva en Tuyalaravel.
     *
     * Usa el endpoint /api/pins/by-reference/reserva_{id} (anadido a
     * Tuyalaravel el 2026-04-28). Razon: nuestro CRM guardaba el
     * provider_code_id en reservas.ttlock_pin_id, pero el GET /api/pins/{id}
     * de Tuyalaravel busca por id interno autoincrement, no por provider_code_id.
     * El nuevo endpoint by-reference es estable y usa el external_reference
     * que SI conocemos siempre (es "reserva_{id}").
     *
     * @return array{state:string, http_status:int, raw:?array}
     */
    public function checkReserva(\App\Models\Reserva $reserva): array
    {
        return $this->checkByReference("reserva_{$reserva->id}");
    }

    /**
     * Lookup directo por external_reference. Util para healthcheck por reserva
     * o cualquier otra etiqueta usada como external_reference (limpiadora,
     * seguridad, etc). NO los purgaremos en la red de seguridad porque el
     * filtro busca por external_reference que empiece por "reserva_".
     *
     * @return array{state:string, http_status:int, raw:?array}
     */
    public function checkByReference(string $reference): array
    {
        $url = config('services.tuya_app.url');
        $key = config('services.tuya_app.api_key');
        if (empty($url) || empty($key)) {
            return ['state' => self::STATE_UNKNOWN, 'http_status' => 0, 'raw' => null];
        }

        try {
            $resp = Http::withHeaders(['X-API-Key' => $key])
                ->timeout(15)
                ->get(rtrim($url, '/') . "/api/pins/by-reference/" . rawurlencode($reference));
        } catch (\Throwable $e) {
            Log::warning('[PinHealth] Excepcion HTTP', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);
            return ['state' => self::STATE_UNKNOWN, 'http_status' => 0, 'raw' => null];
        }

        $status = $resp->status();
        $json = $resp->json();

        if ($status === 404) {
            return ['state' => self::STATE_LOST, 'http_status' => 404, 'raw' => $json];
        }
        if ($status >= 500) {
            return ['state' => self::STATE_UNKNOWN, 'http_status' => $status, 'raw' => $json];
        }
        if ($status >= 400) {
            // 400/401/403 — no decidimos perdido, lo tratamos como unknown
            return ['state' => self::STATE_UNKNOWN, 'http_status' => $status, 'raw' => $json];
        }

        $isActive = (bool) ($json['data']['is_active'] ?? false);
        return [
            'state' => $isActive ? self::STATE_OK : self::STATE_INACTIVE,
            'http_status' => 200,
            'raw' => $json,
        ];
    }

    /**
     * Helper: resuelve el provider_code_id de una reserva. Lo guardamos en
     * `reservas.ttlock_pin_id` por compat historica.
     */
    public function getProviderCodeIdDeReserva(Reserva $reserva): ?int
    {
        $id = $reserva->ttlock_pin_id;
        return $id ? (int) $id : null;
    }
}
