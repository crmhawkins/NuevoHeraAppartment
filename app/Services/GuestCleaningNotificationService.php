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

            // Try WhatsApp first
            $telefono = $cliente->telefono_movil ?? $cliente->telefono ?? null;
            if ($telefono) {
                self::enviarWhatsApp($telefono, $cliente->nombre ?? 'Huésped', $limpiadoraNombre, $apartamentoNombre, $fotosUrl);
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

    private static function enviarWhatsApp(string $telefono, string $nombre, string $limpiadora, string $apartamento, ?string $fotosUrl): void
    {
        $token = Setting::whatsappToken() ?: env('TOKEN_WHATSAPP');
        $phoneId = env('WHATSAPP_PHONE_ID');

        if (empty($token) || empty($phoneId)) {
            Log::warning('[CleaningNotif] WhatsApp no configurado');
            return;
        }

        // Clean phone number
        $telefono = preg_replace('/[^0-9]/', '', $telefono);
        if (strlen($telefono) === 9) $telefono = '34' . $telefono;

        $mensaje = "🏨 *Hawkins Suites*\n\n"
            . "Hola {$nombre},\n\n"
            . "Le informamos que su apartamento *{$apartamento}* ha sido limpiado e higienizado por *{$limpiadora}*.\n\n"
            . "Todo está preparado para su llegada. 🧹✨\n";

        if ($fotosUrl) {
            $mensaje .= "\n📸 Puede ver las fotos de la limpieza aquí:\n{$fotosUrl}\n";
        }

        $mensaje .= "\n¡Le deseamos una estancia agradable!\nHawkins Suites - Apartamentos Algeciras";

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
            Log::info('[CleaningNotif] WhatsApp enviado a ' . $telefono);
        } catch (\Exception $e) {
            Log::error('[CleaningNotif] Error WhatsApp: ' . $e->getMessage());
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
