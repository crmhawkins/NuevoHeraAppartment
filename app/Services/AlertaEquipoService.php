<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Servicio centralizado de alertas al equipo (WhatsApp + Email).
 * Envía notificaciones a los responsables cuando ocurre algo importante.
 */
class AlertaEquipoService
{
    // Números del equipo que reciben alertas
    private static function getDestinatarios(): array
    {
        return [
            'Elena' => '34664368232',
            'David' => '34622440984',
        ];
    }

    /**
     * Enviar alerta por WhatsApp a todos los responsables.
     */
    public static function enviarWhatsApp(string $mensaje, string $tipo = 'info'): void
    {
        $token = Setting::whatsappToken() ?: env('TOKEN_WHATSAPP');
        $phoneId = env('WHATSAPP_PHONE_ID');

        if (empty($token) || empty($phoneId)) {
            Log::warning("[Alerta] No se pudo enviar WhatsApp: token o phoneId vacío", ['tipo' => $tipo]);
            return;
        }

        foreach (self::getDestinatarios() as $nombre => $telefono) {
            try {
                Http::withToken($token)->post(
                    "https://graph.facebook.com/v20.0/{$phoneId}/messages",
                    [
                        'messaging_product' => 'whatsapp',
                        'to' => $telefono,
                        'type' => 'text',
                        'text' => ['body' => $mensaje],
                    ]
                );
                Log::info("[Alerta] WhatsApp enviado a {$nombre}", ['tipo' => $tipo]);
            } catch (\Exception $e) {
                Log::error("[Alerta] Error enviando WhatsApp a {$nombre}", [
                    'tipo' => $tipo,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Enviar alerta por email.
     */
    public static function enviarEmail(string $asunto, string $mensaje, string $destinatario = 'administracion@hawkins.es'): void
    {
        try {
            Mail::raw($mensaje, function ($msg) use ($asunto, $destinatario) {
                $msg->to($destinatario)->subject($asunto);
            });
        } catch (\Exception $e) {
            Log::error("[Alerta] Error enviando email", ['asunto' => $asunto, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Enviar alerta por ambos canales (WhatsApp + Email).
     */
    public static function alertar(string $titulo, string $mensaje, string $tipo = 'info'): void
    {
        $whatsappMsg = "⚠️ {$titulo}\n\n{$mensaje}";
        self::enviarWhatsApp($whatsappMsg, $tipo);
        self::enviarEmail("⚠️ {$titulo}", $mensaje);
    }

    // ============================================================
    // ALERTAS ESPECÍFICAS
    // ============================================================

    /**
     * Alerta: pago de reserva web abandonado.
     */
    public static function pagoAbandonado($reserva): void
    {
        $cliente = $reserva->cliente;
        $nombre = $cliente->nombre ?? 'Desconocido';
        $apartamento = $reserva->apartamento->titulo ?? 'N/A';

        self::alertar(
            'PAGO ABANDONADO',
            "El huésped {$nombre} inició una reserva web pero NO completó el pago.\n\n"
            . "Reserva: #{$reserva->id} ({$reserva->codigo_reserva})\n"
            . "Apartamento: {$apartamento}\n"
            . "Fechas: {$reserva->fecha_entrada} - {$reserva->fecha_salida}\n"
            . "Email: " . ($cliente->email ?? 'N/A') . "\n"
            . "Tel: " . ($cliente->telefono ?? $cliente->telefono_movil ?? 'N/A'),
            'pago_abandonado'
        );
    }

    /**
     * Alerta: fallo en envío a MIR.
     */
    public static function mirFallo($reserva, string $error): void
    {
        $cliente = $reserva->cliente;
        self::alertar(
            'MIR FALLIDO',
            "El envío a MIR ha fallado para la reserva #{$reserva->id}.\n\n"
            . "Código: {$reserva->codigo_reserva}\n"
            . "Cliente: " . ($cliente->nombre ?? '') . " " . ($cliente->apellido1 ?? '') . "\n"
            . "Entrada: {$reserva->fecha_entrada}\n"
            . "Error: {$error}\n\n"
            . "El sistema reintentará a las 10:00 y 22:00.",
            'mir_fallo'
        );
    }

    /**
     * Alerta: scraper Bankinter falló.
     */
    public static function scraperFallo(string $cuenta, string $error): void
    {
        self::alertar(
            'SCRAPER BANKINTER FALLIDO',
            "La importación automática de movimientos bancarios ha fallado.\n\n"
            . "Cuenta: {$cuenta}\n"
            . "Error: {$error}\n\n"
            . "Revisa el PC DeveloperOne o ejecuta el scraper manualmente.",
            'scraper_fallo'
        );
    }

    /**
     * Alerta: nueva reserva recibida por la web.
     */
    public static function nuevaReservaWeb($reserva): void
    {
        $cliente = $reserva->cliente;
        $apartamento = $reserva->apartamento->titulo ?? 'N/A';
        $noches = 0;
        if ($reserva->fecha_entrada && $reserva->fecha_salida) {
            $noches = \Carbon\Carbon::parse($reserva->fecha_entrada)->diffInDays(\Carbon\Carbon::parse($reserva->fecha_salida));
        }

        self::enviarWhatsApp(
            "🏨 NUEVA RESERVA WEB\n\n"
            . "Cliente: " . ($cliente->nombre ?? '') . " " . ($cliente->apellido1 ?? '') . "\n"
            . "Apartamento: {$apartamento}\n"
            . "Fechas: {$reserva->fecha_entrada} → {$reserva->fecha_salida} ({$noches} noches)\n"
            . "Código: {$reserva->codigo_reserva}",
            'nueva_reserva_web'
        );
    }

    /**
     * Alerta: fallo en envio de informe trimestral a asesoria.
     */
    public static function asesoriaFallo(string $asesoria, string $email, string $error): void
    {
        self::alertar(
            "FALLO ENVIO ASESORIA",
            "No se ha podido enviar el informe trimestral a la asesoria.

"
            . "Asesoria: {$asesoria}
"
            . "Email: {$email}
"
            . "Error: {$error}

"
            . "Revisa la configuracion de email y reintenta manualmente.",
            "asesoria_fallo"
        );
    }
}
