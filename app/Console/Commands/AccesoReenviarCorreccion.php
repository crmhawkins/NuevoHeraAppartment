<?php

namespace App\Console\Commands;

use App\Models\Reserva;
use App\Models\Setting;
use App\Models\WhatsappMensaje;
use App\Http\Controllers\WebhookController;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * [2026-04-25] Comando one-shot que avisa a los huespedes afectados por
 * el bug del codigo_emergencia_portal '0001981' que se mostro como clave
 * del apartamento. Envia por 3 canales (WhatsApp + email + chat
 * Booking/Airbnb via Channex) la correccion con la clave correcta del
 * apartamento.
 *
 *   php artisan acceso:reenviar-correccion --dry-run
 *   php artisan acceso:reenviar-correccion --reserva=6368
 *   php artisan acceso:reenviar-correccion
 */
class AccesoReenviarCorreccion extends Command
{
    protected $signature = 'acceso:reenviar-correccion
        {--dry-run : solo lista, no envia}
        {--reserva= : enviar solo a una reserva concreta}
        {--codigo=0001981 : codigo de acceso erroneo a buscar}';

    protected $description = 'Reenvia la clave correcta del apartamento a los huespedes que recibieron por error el codigo de emergencia del portal.';

    /**
     * Plantillas multi-idioma. {nombre}, {apt}, {edif}, {portal}, {clave}.
     */
    private const PLANTILLAS = [
        'es' => [
            'asunto' => 'Corrección importante: claves de acceso a tu apartamento',
            'cuerpo' => "Estimado/a {nombre},\n\nLa clave de acceso a tu apartamento {apt} del edificio {edif} ha sido modificada por error en un mensaje anterior.\n\nLas claves correctas son:\n\n🚪 Para entrar al edificio: {portal}\n🔑 Para tu apartamento ({apt}): {clave}\n\nDisculpa las molestias. Si tienes cualquier problema durante el check-in, contáctanos por este mismo medio.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'en' => [
            'asunto' => 'Important correction: access codes to your apartment',
            'cuerpo' => "Dear {nombre},\n\nThe access code for your apartment {apt} in the {edif} building was sent incorrectly in a previous message.\n\nThe correct codes are:\n\n🚪 Building entrance: {portal}\n🔑 Your apartment ({apt}): {clave}\n\nWe apologise for the inconvenience. If you have any problem during check-in, contact us through this same channel.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'fr' => [
            'asunto' => 'Correction importante : codes d\'accès à votre appartement',
            'cuerpo' => "Cher/Chère {nombre},\n\nLe code d'accès à votre appartement {apt} dans l'immeuble {edif} a été envoyé de manière incorrecte dans un message précédent.\n\nLes codes corrects sont :\n\n🚪 Entrée de l'immeuble : {portal}\n🔑 Votre appartement ({apt}) : {clave}\n\nNous nous excusons pour la gêne occasionnée. En cas de problème pendant le check-in, contactez-nous par ce même canal.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'de' => [
            'asunto' => 'Wichtige Korrektur: Zugangscodes zu Ihrer Wohnung',
            'cuerpo' => "Sehr geehrte/r {nombre},\n\nder Zugangscode für Ihre Wohnung {apt} im Gebäude {edif} wurde in einer vorherigen Nachricht fehlerhaft übermittelt.\n\nDie korrekten Codes sind:\n\n🚪 Hauseingang: {portal}\n🔑 Ihre Wohnung ({apt}): {clave}\n\nWir entschuldigen uns für die Unannehmlichkeiten. Bei Problemen beim Check-in kontaktieren Sie uns über denselben Kanal.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'it' => [
            'asunto' => 'Correzione importante: codici di accesso al tuo appartamento',
            'cuerpo' => "Gentile {nombre},\n\nIl codice di accesso al tuo appartamento {apt} nell'edificio {edif} è stato inviato in modo errato in un messaggio precedente.\n\nI codici corretti sono:\n\n🚪 Ingresso dell'edificio: {portal}\n🔑 Il tuo appartamento ({apt}): {clave}\n\nCi scusiamo per il disagio. In caso di problemi durante il check-in, contattaci tramite lo stesso canale.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'pt_PT' => [
            'asunto' => 'Correção importante: códigos de acesso ao seu apartamento',
            'cuerpo' => "Caro/a {nombre},\n\nO código de acesso ao seu apartamento {apt} no edifício {edif} foi enviado incorretamente numa mensagem anterior.\n\nOs códigos corretos são:\n\n🚪 Entrada do edifício: {portal}\n🔑 O seu apartamento ({apt}): {clave}\n\nPedimos desculpa pelo incómodo. Em caso de problema durante o check-in, contacte-nos pelo mesmo canal.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
        'ar' => [
            'asunto' => 'تصحيح مهم: رموز الدخول إلى شقتك',
            'cuerpo' => "عزيزي/عزيزتي {nombre}،\n\nتم إرسال رمز الدخول إلى شقتك {apt} في مبنى {edif} بشكل خاطئ في رسالة سابقة.\n\nالرموز الصحيحة هي:\n\n🚪 لدخول المبنى: {portal}\n🔑 لشقتك ({apt}): {clave}\n\nنعتذر عن الإزعاج. إذا واجهت أي مشكلة أثناء تسجيل الوصول، تواصل معنا عبر نفس القناة.\n\nHawkins Suites · Apartamentos Algeciras",
        ],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $codigoMalo = (string) $this->option('codigo');
        $reservaId = $this->option('reserva');

        $q = Reserva::with('cliente', 'apartamento.edificio')
            ->where('codigo_acceso', $codigoMalo);
        if ($reservaId) $q->where('id', (int) $reservaId);
        $reservas = $q->get();

        if ($reservas->isEmpty()) {
            $this->info("No hay reservas con codigo_acceso='{$codigoMalo}'");
            return self::SUCCESS;
        }

        $this->info("Procesando {$reservas->count()} reservas" . ($dry ? ' (DRY-RUN, no se envia)' : ''));

        $stats = ['total' => 0, 'wa_ok' => 0, 'wa_fail' => 0, 'mail_ok' => 0, 'mail_fail' => 0, 'channex_ok' => 0, 'channex_fail' => 0];

        foreach ($reservas as $r) {
            $stats['total']++;
            $cliente = $r->cliente;
            $apt = $r->apartamento;
            if (!$cliente || !$apt) {
                $this->warn(" #{$r->id}: sin cliente o apartamento, saltando");
                continue;
            }

            $idioma = $this->resolverIdioma($cliente->nacionalidad ?? null);
            $tmpl = self::PLANTILLAS[$idioma] ?? self::PLANTILLAS['es'];

            $sustituciones = [
                '{nombre}' => $cliente->nombre ?: ($cliente->alias ?: 'Huésped'),
                '{apt}'    => $apt->titulo ?: 'apartamento',
                '{edif}'   => $apt->edificio->nombre ?? 'Hawkins',
                '{portal}' => $r->codigo_acceso ?: ($apt->edificio->clave ?? '—'),
                '{clave}'  => $apt->claves ?: '—',
            ];
            $cuerpo = strtr($tmpl['cuerpo'], $sustituciones);
            $asunto = strtr($tmpl['asunto'], $sustituciones);

            $this->line(" #{$r->id} · {$sustituciones['{nombre}']} · {$sustituciones['{apt}']} · idioma={$idioma}");

            if ($dry) continue;

            // 1) WhatsApp (texto libre, mejor esfuerzo — ventana 24h)
            $tel = $cliente->telefono_movil ?: $cliente->telefono ?: null;
            if ($tel) {
                if ($this->enviarWhatsapp($tel, $cuerpo)) $stats['wa_ok']++;
                else                                       $stats['wa_fail']++;
            }

            // 2) Email
            $email = $cliente->email ?: null;
            if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                try {
                    Mail::raw($cuerpo, function ($m) use ($email, $asunto) {
                        $m->to($email)->subject($asunto);
                    });
                    $stats['mail_ok']++;
                    Log::info('[acceso:reenviar] Email enviado', ['reserva' => $r->id, 'email' => $email]);
                } catch (\Throwable $e) {
                    $stats['mail_fail']++;
                    Log::warning('[acceso:reenviar] Email fallo', ['reserva' => $r->id, 'error' => $e->getMessage()]);
                }
            }

            // 3) Channex (chat Booking/Airbnb)
            if (!empty($r->id_channex)) {
                try {
                    $res = WebhookController::enviarMensajeAutomaticoAChannex($cuerpo, $r->id_channex);
                    if ($res !== false && $res !== null) {
                        $stats['channex_ok']++;
                    } else {
                        $stats['channex_fail']++;
                    }
                } catch (\Throwable $e) {
                    $stats['channex_fail']++;
                    Log::warning('[acceso:reenviar] Channex fallo', ['reserva' => $r->id, 'error' => $e->getMessage()]);
                }
            }

            // pequena pausa para evitar rate limits
            usleep(300_000); // 0.3s
        }

        $this->line('');
        $this->info('Resumen: ' . json_encode($stats));
        return self::SUCCESS;
    }

    private function resolverIdioma(?string $nacionalidad): string
    {
        $n = strtoupper(trim((string) $nacionalidad));
        $mapa = [
            'ES' => 'es', 'ESP' => 'es',
            'GB' => 'en', 'IE' => 'en', 'US' => 'en', 'CA' => 'en', 'AU' => 'en', 'GBR' => 'en', 'IRL' => 'en', 'USA' => 'en',
            'FR' => 'fr', 'BE' => 'fr', 'CH' => 'fr', 'FRA' => 'fr',
            'DE' => 'de', 'AT' => 'de', 'DEU' => 'de', 'AUT' => 'de',
            'IT' => 'it', 'ITA' => 'it',
            'PT' => 'pt_PT', 'BR' => 'pt_PT', 'PRT' => 'pt_PT',
            'MA' => 'ar', 'DZ' => 'ar', 'TN' => 'ar', 'EG' => 'ar', 'SA' => 'ar', 'AE' => 'ar', 'MAR' => 'ar',
        ];
        return $mapa[$n] ?? 'es';
    }

    private function enviarWhatsapp(string $telefono, string $cuerpo): bool
    {
        $token = Setting::whatsappToken();
        $url   = Setting::whatsappUrl();
        if (!$token || !$url) return false;

        $tel = preg_replace('/\D+/', '', $telefono);
        if ($tel === '') return false;
        if (strlen($tel) === 9) $tel = '34' . $tel;

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $tel,
            'type'              => 'text',
            'text'              => ['body' => $cuerpo],
        ];

        try {
            $resp = Http::withToken($token)->timeout(15)->post($url, $payload);
            $wamid = $resp->json('messages.0.id');
            if ($resp->successful() && $wamid) {
                try {
                    WhatsappMensaje::create([
                        'mensaje_id'    => $wamid,
                        'tipo'          => 'text',
                        'contenido'     => "[correccion claves] " . $cuerpo,
                        'remitente'     => 'SYSTEM',
                        'recipient_id'  => $wamid,
                        'fecha_mensaje' => now(),
                        'metadata'      => $payload,
                        'estado'        => 'accepted',
                    ]);
                } catch (\Throwable $e) { /* ignora */ }
                Log::info('[acceso:reenviar] WhatsApp enviado', ['telefono' => $tel, 'wamid' => $wamid]);
                return true;
            }
            $body = (string) $resp->body();
            $is131047 = str_contains($body, '131047');
            Log::warning('[acceso:reenviar] WhatsApp NO entregado', [
                'telefono' => $tel,
                'status'   => $resp->status(),
                'motivo'   => $is131047 ? 'ventana 24h cerrada — pero email/Channex SI llegan' : 'otro',
                'body'     => mb_substr($body, 0, 250),
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::warning('[acceso:reenviar] WhatsApp excepcion', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
