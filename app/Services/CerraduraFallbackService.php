<?php

namespace App\Services;

use App\Models\Edificio;
use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-19] Sistema de fallback para cerraduras digitales.
 *
 * Cuando la cerradura de un edificio deja de aceptar nuevos PINs (p.ej.
 * porque Tuya Cloud cambió la API, o la cerradura quedó offline), este
 * servicio:
 *   1. Detecta el fallo al intentar programar un PIN.
 *   2. Tras UMBRAL_FALLOS consecutivos, activa automaticamente el "modo
 *      fallback" para ese edificio/proveedor.
 *   3. En modo fallback, las reservas reciben el codigo_emergencia_portal
 *      (un PIN estatico pre-configurado en la cerradura, por ejemplo
 *      "0001981") en lugar de un PIN unico.
 *   4. A los huespedes que YA recibieron un PIN unico (y están por
 *      llegar) se les envia un segundo WhatsApp avisando del cambio al
 *      codigo de emergencia.
 *   5. Alerta WhatsApp al admin una sola vez por activacion.
 *
 * Desactivacion es manual (via comando artisan o UI admin), porque
 * auto-recuperar seria peligroso — un falso "recovery" dejaria a los
 * huespedes sin acceso.
 */
class CerraduraFallbackService
{
    public const PROVIDER_TUYA   = 'tuya';
    public const PROVIDER_TTLOCK = 'ttlock';

    public const UMBRAL_FALLOS = 3;
    public const VENTANA_REENVIO_DIAS = 7;

    /**
     * Registrar un fallo al programar PIN. Si se alcanza el umbral,
     * activa automaticamente el modo fallback del edificio+proveedor.
     */
    public function registrarFallo(Edificio $edificio, string $proveedor, string $motivo): void
    {
        $proveedor = strtolower($proveedor);
        if (!in_array($proveedor, [self::PROVIDER_TUYA, self::PROVIDER_TTLOCK], true)) {
            return;
        }

        $campoCount  = "fallos_consecutivos_{$proveedor}";
        $campoActivo = "fallback_{$proveedor}_activo";
        $campoAt     = "fallback_{$proveedor}_activado_at";

        $edificio->{$campoCount} = (int) $edificio->{$campoCount} + 1;
        $edificio->save();

        Log::warning("[Fallback] Fallo programando {$proveedor} en edificio {$edificio->id}", [
            'fallos_consecutivos' => $edificio->{$campoCount},
            'umbral' => self::UMBRAL_FALLOS,
            'motivo' => $motivo,
        ]);

        if ($edificio->{$campoCount} >= self::UMBRAL_FALLOS && !$edificio->{$campoActivo}) {
            $this->activarFallback($edificio, $proveedor, $motivo);
        }
    }

    /**
     * Resetea el contador de fallos cuando una programacion va bien.
     * NO desactiva automaticamente el modo fallback si ya esta activo —
     * eso es manual.
     */
    public function registrarExito(Edificio $edificio, string $proveedor): void
    {
        $proveedor = strtolower($proveedor);
        $campoCount = "fallos_consecutivos_{$proveedor}";
        if (!in_array($proveedor, [self::PROVIDER_TUYA, self::PROVIDER_TTLOCK], true)) {
            return;
        }
        if ((int) $edificio->{$campoCount} > 0) {
            $edificio->{$campoCount} = 0;
            $edificio->save();
        }
    }

    /**
     * Activa modo fallback para un edificio+proveedor. Notifica al admin
     * y re-avisa a los huespedes con check-in proximo del cambio.
     */
    public function activarFallback(Edificio $edificio, string $proveedor, string $motivo = ''): void
    {
        $proveedor = strtolower($proveedor);
        $campoActivo = "fallback_{$proveedor}_activo";
        $campoAt     = "fallback_{$proveedor}_activado_at";

        $edificio->{$campoActivo} = true;
        $edificio->{$campoAt} = now();
        $edificio->save();

        Log::alert("[Fallback] MODO FALLBACK ACTIVADO en edificio {$edificio->id} ({$edificio->nombre}) proveedor {$proveedor}");

        $this->alertarAdmin($edificio, $proveedor, $motivo);
        $this->reenviarCodigoEmergenciaAReservasProximas($edificio);
    }

    /**
     * Desactivacion manual del modo fallback.
     */
    public function desactivarFallback(Edificio $edificio, string $proveedor): void
    {
        $proveedor = strtolower($proveedor);
        $campoActivo = "fallback_{$proveedor}_activo";
        $campoAt     = "fallback_{$proveedor}_activado_at";
        $campoCount  = "fallos_consecutivos_{$proveedor}";

        $edificio->{$campoActivo} = false;
        $edificio->{$campoAt} = null;
        $edificio->{$campoCount} = 0;
        $edificio->save();

        try {
            $wa = app(WhatsappNotificationService::class);
            $wa->sendToConfiguredRecipients(
                "✅ Cerradura " . strtoupper($proveedor) . " vuelve a funcionar en " . ($edificio->nombre ?? "edificio {$edificio->id}") .
                ". Modo fallback desactivado — las proximas reservas recibiran PIN unico normal."
            );
        } catch (\Throwable $e) { /* ignora */ }
    }

    /**
     * Devuelve true si ese proveedor esta en modo fallback en este edificio.
     */
    public function estaEnFallback(Edificio $edificio, string $proveedor): bool
    {
        $proveedor = strtolower($proveedor);
        $campoActivo = "fallback_{$proveedor}_activo";
        return (bool) $edificio->{$campoActivo};
    }

    /**
     * WhatsApp al admin avisando del modo fallback.
     */
    private function alertarAdmin(Edificio $edificio, string $proveedor, string $motivo): void
    {
        try {
            $wa = app(WhatsappNotificationService::class);
            $codigoEmerg = $edificio->codigo_emergencia_portal ?: '(sin configurar)';
            $msg = "⚠️ CERRADURA " . strtoupper($proveedor) . " CAIDA\n\n"
                . "Edificio: " . ($edificio->nombre ?? "id {$edificio->id}") . "\n"
                . "Tras " . self::UMBRAL_FALLOS . " fallos consecutivos, activamos modo fallback.\n"
                . "Las reservas recibiran el codigo de emergencia: *{$codigoEmerg}* hasta que resuelvas.\n\n"
                . "Motivo ultimo fallo: " . mb_substr($motivo, 0, 200) . "\n\n"
                . "Para desactivar el fallback cuando la cerradura vuelva: php artisan cerraduras:desactivar-fallback {$edificio->id} {$proveedor}";
            $wa->sendToConfiguredRecipients($msg);
        } catch (\Throwable $e) {
            Log::error("[Fallback] No se pudo enviar alerta admin: " . $e->getMessage());
        }
    }

    /**
     * Re-notifica a los huespedes con reserva activa (check-in dentro de
     * los proximos VENTANA_REENVIO_DIAS) que ya recibieron un PIN unico
     * — el PIN anterior puede no funcionar; deben usar el de emergencia.
     *
     * Solo envia una vez por reserva (flag codigo_fallback_enviado).
     */
    private function reenviarCodigoEmergenciaAReservasProximas(Edificio $edificio): void
    {
        $codigoEmerg = $edificio->codigo_emergencia_portal;
        if (empty($codigoEmerg)) {
            Log::warning("[Fallback] edificio {$edificio->id} sin codigo_emergencia_portal configurado, no se reenvia a reservas");
            return;
        }

        $desde = Carbon::now()->subDays(3)->toDateString(); // incluye huespedes ya dentro
        $hasta = Carbon::now()->addDays(self::VENTANA_REENVIO_DIAS)->toDateString();

        $reservas = Reserva::with('cliente','apartamento')
            ->whereHas('apartamento', fn($q) => $q->where('edificio_id', $edificio->id))
            ->where(function($q) {
                $q->where('codigo_enviado_cerradura', 1)->orWhereNotNull('codigo_acceso');
            })
            ->where('codigo_fallback_enviado', false)
            ->whereBetween('fecha_entrada', [$desde, $hasta])
            ->where('estado_id', '!=', 4) // no canceladas
            ->get();

        Log::info("[Fallback] Reenviando codigo emergencia a " . $reservas->count() . " reservas del edificio {$edificio->id}");

        foreach ($reservas as $r) {
            $this->enviarCodigoEmergenciaACliente($r, $edificio);
        }
    }

    /**
     * Envia WhatsApp al cliente con el codigo de emergencia. Marca la
     * reserva con codigo_fallback_enviado=1 para no reenviar.
     */
    public function enviarCodigoEmergenciaACliente(Reserva $reserva, ?Edificio $edificio = null): bool
    {
        $edificio = $edificio ?? $reserva->apartamento?->edificioName ?? null;
        if (!$edificio) return false;
        $codigoEmerg = $edificio->codigo_emergencia_portal;
        if (empty($codigoEmerg)) return false;

        $phone = $reserva->cliente?->telefono_movil ?? $reserva->cliente?->telefono ?? null;
        if (empty($phone)) {
            Log::warning("[Fallback] Reserva {$reserva->id}: cliente sin telefono, no se puede avisar");
            return false;
        }

        $fechaEntradaFmt = Carbon::parse($reserva->fecha_entrada)->format('d/m/Y');
        $fechaSalidaFmt  = Carbon::parse($reserva->fecha_salida)->format('d/m/Y');

        $msg  = "⚠️ Cambio en el código de acceso\n\n";
        $msg .= "El código anterior que te enviamos para el portal YA NO FUNCIONA.\n\n";
        $msg .= "Nuevo código de acceso: *{$codigoEmerg}* (pulsa # después)\n\n";
        $msg .= "Valido del {$fechaEntradaFmt} al {$fechaSalidaFmt}.\n\n";
        $msg .= "Perdona las molestias, estamos resolviendo una incidencia con la cerradura.";

        try {
            $this->enviarWhatsAppTexto($phone, $msg);
            $reserva->codigo_fallback_enviado = true;
            $reserva->save();
            Log::info("[Fallback] Reserva {$reserva->id}: codigo emergencia enviado al cliente");
            return true;
        } catch (\Throwable $e) {
            Log::error("[Fallback] Error enviando codigo emergencia reserva {$reserva->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Helper: envía mensaje simple por WhatsApp. Usa el WhatsappToolsApiController
     * o similar para no depender del template "reparaciones" que va a admin.
     */
    private function enviarWhatsAppTexto(string $phone, string $texto): void
    {
        $token = \App\Models\Setting::whatsappToken();
        $url = \App\Models\Setting::whatsappUrl();

        $phone = preg_replace('/\D+/', '', $phone);
        if ($phone !== '' && !str_starts_with($phone, '34') && strlen($phone) === 9) {
            $phone = '34' . $phone;
        }

        \Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $token,
            ])
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $phone,
                'type'              => 'text',
                'text'              => ['body' => $texto],
            ])
            ->throw();
    }
}
