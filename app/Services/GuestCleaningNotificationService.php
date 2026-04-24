<?php

namespace App\Services;

use App\Models\ApartamentoLimpieza;
use App\Models\Reserva;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class GuestCleaningNotificationService
{
    /**
     * Send cleaning completion notification to the guest.
     * Only sends if there's an upcoming reservation within 3 days.
     */
    public static function notificar(ApartamentoLimpieza $limpieza): void
    {
        try {
            // Find the associated reservation
            $reserva = null;

            // First try direct association
            if ($limpieza->reserva_id) {
                $reserva = Reserva::with('cliente')->find($limpieza->reserva_id);
            }

            // Fallback: find next upcoming reservation for this apartment
            if (!$reserva && $limpieza->apartamento_id) {
                $reserva = Reserva::with('cliente')
                    ->where('apartamento_id', $limpieza->apartamento_id)
                    ->whereIn('estado_id', [1, 2]) // confirmed or pending
                    ->where('fecha_entrada', '>=', Carbon::today())
                    ->where('fecha_entrada', '<=', Carbon::today()->addDays(3))
                    ->orderBy('fecha_entrada', 'asc')
                    ->first();
            }

            if (!$reserva || !$reserva->cliente) {
                Log::info('[CleaningNotif] No upcoming reservation found for limpieza #' . $limpieza->id);
                return;
            }

            $cliente = $reserva->cliente;
            $limpiadoraNombre = $limpieza->empleada ? $limpieza->empleada->name : 'nuestro equipo';
            $apartamentoNombre = $limpieza->apartamento ? $limpieza->apartamento->titulo : 'su apartamento';

            // Generate the cleaning photos URL
            $fotosUrl = $reserva->token
                ? url('/apartamento-limpio/' . $reserva->token)
                : null;

            // Resolver el idioma del cliente a partir de la nacionalidad.
            try {
                $clienteSvc = app(\App\Services\ClienteService::class);
                $idioma = $clienteSvc->idiomaCodigo($cliente->nacionalidad ?? null) ?: 'es';
            } catch (\Throwable $e) {
                $idioma = 'es';
            }

            // Try WhatsApp first
            $telefono = $cliente->telefono_movil ?? $cliente->telefono ?? null;
            if ($telefono) {
                self::enviarWhatsApp(
                    $telefono,
                    $cliente->nombre ?? 'Huésped',
                    $limpiadoraNombre,
                    $apartamentoNombre,
                    $fotosUrl,
                    $idioma,
                    $reserva->token ?? null
                );
            }

            // Also send email
            $email = $cliente->email ?? null;
            if ($email) {
                self::enviarEmail($email, $cliente->nombre ?? 'Huésped', $limpiadoraNombre, $apartamentoNombre, $fotosUrl);
            }

            Log::info('[CleaningNotif] Notificación enviada para limpieza #' . $limpieza->id, [
                'reserva' => $reserva->id,
                'telefono' => $telefono ? 'sí' : 'no',
                'email' => $email ? 'sí' : 'no',
            ]);

        } catch (\Exception $e) {
            Log::error('[CleaningNotif] Error: ' . $e->getMessage(), [
                'limpieza_id' => $limpieza->id,
            ]);
        }
    }

    /**
     * Envia el mensaje al huesped. Preferencia 1: template 'limpieza_completada'
     * en el idioma del cliente (bypasea ventana 24h). Fallback: texto libre
     * (solo llega si el cliente escribio al business en las ultimas 24h).
     *
     * [2026-04-24] Reescrito para:
     *  - Capturar la respuesta de Meta y detectar error 131047 (ventana 24h).
     *  - Persistir en whatsapp_mensajes para auditoria y tracking de entregas.
     *  - Loguear WARNING real si falla, no un falso "enviado".
     *  - Limpiar el numero de telefono antes de enviar.
     *
     * @param string $idioma codigo ISO: es, en, fr, de, it, pt_PT, ar
     * @param string|null $tokenReserva token para la URL del apartado de fotos
     */
    private static function enviarWhatsApp(
        string $telefono,
        string $nombre,
        string $limpiadora,
        string $apartamento,
        ?string $fotosUrl,
        string $idioma = 'es',
        ?string $tokenReserva = null
    ): bool {
        $token = Setting::whatsappToken() ?: env('TOKEN_WHATSAPP');
        $url   = Setting::whatsappUrl(); // incluye phone_id

        if (empty($token) || empty($url)) {
            Log::warning('[CleaningNotif] WhatsApp no configurado (falta token/url)');
            return false;
        }

        // Limpiar numero
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($telefono) === 9) $telefono = '34' . $telefono;

        // 1. Preferencia: template aprobado en el idioma del cliente.
        $tmpl = \App\Models\WhatsappTemplate::where('name', 'limpieza_completada')
            ->where('language', $idioma)
            ->where('status', 'APPROVED')
            ->first();

        // Fallback: si no esta en el idioma del cliente, probamos 'es' y 'en'.
        if (!$tmpl) {
            $tmpl = \App\Models\WhatsappTemplate::where('name', 'limpieza_completada')
                ->whereIn('language', ['es', 'en'])
                ->where('status', 'APPROVED')
                ->orderByRaw("CASE language WHEN 'es' THEN 0 ELSE 1 END")
                ->first();
        }

        if ($tmpl && $tokenReserva) {
            return self::enviarTemplate($telefono, $tmpl, $nombre, $apartamento, $limpiadora, $tokenReserva, $token, $url);
        }

        // 2. Fallback a texto libre. Solo llega si el cliente escribio al business
        // en las ultimas 24h (Meta devolvera error 131047 si no).
        return self::enviarTextoLibre($telefono, $nombre, $limpiadora, $apartamento, $fotosUrl, $token, $url);
    }

    private static function enviarTemplate(string $telefono, $tmpl, string $nombre, string $apartamento, string $limpiadora, string $tokenReserva, string $token, string $url): bool
    {
        // Meta rechaza parametros con \n / \t / mas de 4 espacios consecutivos.
        $clean = function ($s) {
            $s = (string) $s;
            $s = preg_replace("/[\r\n\t]+/", ' ', $s);
            $s = preg_replace('/ {2,}/', ' ', $s);
            return trim($s) ?: '-';
        };

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $telefono,
            'type'              => 'template',
            'template' => [
                'name'     => 'limpieza_completada',
                'language' => ['code' => $tmpl->language],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            ['type' => 'text', 'text' => $clean($nombre)],
                            ['type' => 'text', 'text' => $clean($apartamento)],
                            ['type' => 'text', 'text' => $clean($limpiadora)],
                        ],
                    ],
                    [
                        'type'     => 'button',
                        'sub_type' => 'url',
                        'index'    => 0,
                        'parameters' => [
                            ['type' => 'text', 'text' => $tokenReserva],
                        ],
                    ],
                ],
            ],
        ];

        try {
            $resp = Http::withToken($token)->timeout(15)->post($url, $payload);
            if ($resp->successful()) {
                $wamid = $resp->json('messages.0.id');
                self::persistir($wamid, 'limpieza_template', json_encode($payload), $payload);
                Log::info('[CleaningNotif] Template limpieza enviado', [
                    'telefono' => $telefono, 'wamid' => $wamid, 'lang' => $tmpl->language,
                ]);
                return true;
            }
            Log::error('[CleaningNotif] Template limpieza rechazado', [
                'telefono' => $telefono,
                'status'   => $resp->status(),
                'body'     => mb_substr((string) $resp->body(), 0, 400),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[CleaningNotif] Excepcion enviando template: ' . $e->getMessage());
            return false;
        }
    }

    private static function enviarTextoLibre(string $telefono, string $nombre, string $limpiadora, string $apartamento, ?string $fotosUrl, string $token, string $url): bool
    {
        $mensaje = "🏨 *Hawkins Suites*\n\n"
            . "Hola {$nombre},\n\n"
            . "Le informamos que su apartamento *{$apartamento}* ha sido limpiado e higienizado por *{$limpiadora}*.\n\n"
            . "Todo está preparado para su llegada. 🧹✨\n";
        if ($fotosUrl) {
            $mensaje .= "\n📸 Puede ver las fotos de la limpieza aquí:\n{$fotosUrl}\n";
        }
        $mensaje .= "\n¡Le deseamos una estancia agradable!\nHawkins Suites - Apartamentos Algeciras";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => $telefono,
            'type'              => 'text',
            'text'              => ['body' => $mensaje],
        ];

        try {
            $resp = Http::withToken($token)->timeout(15)->post($url, $payload);
            if ($resp->successful()) {
                $wamid = $resp->json('messages.0.id');
                self::persistir($wamid, 'limpieza_texto', $mensaje, $payload);
                Log::info('[CleaningNotif] Texto libre enviado (requiere ventana 24h)', [
                    'telefono' => $telefono, 'wamid' => $wamid,
                ]);
                return true;
            }
            // Detectar error 131047 (ventana 24h cerrada) y loguearlo claramente.
            $body = (string) $resp->body();
            $is131047 = str_contains($body, '131047') || str_contains($body, 'Re-engagement message');
            Log::warning('[CleaningNotif] Texto libre NO entregado por Meta', [
                'telefono' => $telefono,
                'status'   => $resp->status(),
                'motivo'   => $is131047 ? 'ventana 24h cerrada (131047) — usar template aprobado' : 'otro',
                'body'     => mb_substr($body, 0, 400),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('[CleaningNotif] Excepcion enviando texto libre: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Persiste el mensaje saliente en whatsapp_mensajes para que aparezca
     * en el panel de conversaciones del admin y para que los webhooks de
     * status de Meta puedan actualizarlo.
     */
    private static function persistir(?string $wamid, string $tipo, string $contenido, array $metadata): void
    {
        if (!$wamid) return;
        try {
            \App\Models\WhatsappMensaje::create([
                'mensaje_id'    => $wamid,
                'tipo'          => $tipo,
                'contenido'     => $contenido,
                'remitente'     => 'SYSTEM',
                'recipient_id'  => $wamid,
                'fecha_mensaje' => now(),
                'metadata'      => $metadata,
                'estado'        => 'accepted',
            ]);
        } catch (\Throwable $e) {
            Log::warning('[CleaningNotif] No se pudo persistir mensaje: ' . $e->getMessage());
        }
    }

    private static function enviarEmail(string $email, string $nombre, string $limpiadora, string $apartamento, ?string $fotosUrl): void
    {
        try {
            $asunto = "Su apartamento {$apartamento} está listo - Hawkins Suites";
            $cuerpo = "Estimado/a {$nombre},\n\n"
                . "Le informamos que su apartamento {$apartamento} ha sido limpiado e higienizado por {$limpiadora}.\n\n"
                . "Todo está preparado para su llegada.\n";

            if ($fotosUrl) {
                $cuerpo .= "\nPuede ver las fotos de la limpieza en: {$fotosUrl}\n";
            }

            $cuerpo .= "\n¡Le deseamos una estancia agradable!\nHawkins Suites - Apartamentos Algeciras";

            Mail::raw($cuerpo, function ($msg) use ($email, $asunto) {
                $msg->to($email)->subject($asunto);
            });
            Log::info('[CleaningNotif] Email enviado a ' . $email);
        } catch (\Exception $e) {
            Log::error('[CleaningNotif] Error email: ' . $e->getMessage());
        }
    }
}
