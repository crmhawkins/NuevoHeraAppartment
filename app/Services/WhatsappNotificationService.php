<?php

namespace App\Services;

use App\Models\EmailNotificaciones;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

class WhatsappNotificationService
{
    /**
     * Template preferente para alertas internas. Si no esta aprobado en Meta
     * todavia, caemos a 'alerta_doble_reserva' que ya lleva tiempo aprobado
     * (aunque su HEADER fijo no encaje 100% con todas las alertas).
     */
    private string $templateName = 'alerta_doble_reserva';

    /**
     * [2026-04-24] Enviar alerta al admin. SIEMPRE se manda como TEMPLATE
     * (categoria UTILITY) para que no dependa de la ventana de 24h de
     * WhatsApp Business — los textos libres solo llegan si el destinatario
     * ha escrito al business en las ultimas 24h; los templates UTILITY
     * llegan siempre. El problema que Elena estuvo sin recibir alertas
     * durante dias venia de aqui (error 131047 "Re-engagement message").
     */
    public function sendToConfiguredRecipients(string $message, array $templateVariables = []): void
    {
        $destinatarios = EmailNotificaciones::whereNotNull('telefono')
            ->where('telefono', '!=', '')
            ->get();

        if ($destinatarios->isEmpty()) {
            Log::info('WhatsappNotificationService: sin destinatarios configurados');
            return;
        }

        Log::info('WhatsappNotificationService: iniciando envío a responsables', [
            'total_destinatarios' => $destinatarios->count(),
            'ids' => $destinatarios->pluck('id'),
        ]);

        // Resolver template y variables a enviar.
        [$tmplName, $tmplVars] = $this->resolverTemplateAlerta($message, $templateVariables);

        foreach ($destinatarios as $destinatario) {
            Log::info('WhatsappNotificationService: preparando envío', [
                'destinatario_id' => $destinatario->id,
                'telefono' => $destinatario->telefono,
                'nombre' => $destinatario->nombre,
                'template' => $tmplName,
            ]);

            $this->sendAlertTemplate($destinatario->telefono, $tmplName, $tmplVars, $message);
        }
    }

    /**
     * Decide que template usar y como partir el mensaje en las variables
     * que ese template espera. Prefiere 'alerta_sistema_hawkins' (1 var,
     * texto libre) si esta aprobado; si no, usa 'alerta_doble_reserva'
     * (4 vars, particiona el mensaje).
     *
     * Si el caller ya pasa $templateVariables (uso historico para doble
     * reserva), los respeta.
     *
     * @return array{0:string,1:array<int,string>}
     */
    private function resolverTemplateAlerta(string $message, array $templateVariables): array
    {
        // Caller ya especifico variables -> mantener template por defecto.
        if (!empty($templateVariables)) {
            return [$this->templateName, array_values($templateVariables)];
        }

        // Preferencia 1: template de 1-variable aprobado.
        $tmplUni = \App\Models\WhatsappTemplate::where('name', 'alerta_sistema_hawkins')
            ->where('status', 'APPROVED')
            ->first();
        if ($tmplUni) {
            // [FIX 2026-04-24] Meta rechaza parametros con new-line/tab o
            // mas de 4 espacios consecutivos (error #100 "Invalid parameter").
            // Normalizamos: saltos de linea -> " · ", tabs -> espacio,
            // espacios consecutivos -> uno solo. Tambien truncamos a 1024
            // chars (limite de template param).
            $texto = str_replace(["\r\n", "\r"], "\n", (string) $message);
            $texto = preg_replace('/[\t\v\f]+/', ' ', $texto);
            $texto = preg_replace('/\n+/', ' · ', $texto);
            $texto = preg_replace('/ {2,}/', ' ', $texto);
            $texto = trim($texto);
            if (mb_strlen($texto) > 1024) $texto = mb_substr($texto, 0, 1000) . '…';
            return ['alerta_sistema_hawkins', [$texto]];
        }

        // Fallback: template historico 'alerta_doble_reserva' (4 vars).
        // Lo usamos como contenedor generico. El HEADER fijo "Doble reserva
        // detectada" sera impreciso hasta que se apruebe alerta_sistema_hawkins.
        $linea1 = mb_substr(strtok($message, "\n") ?: '', 0, 60);
        strtok('', ''); // reset
        $resto = trim(mb_substr($message, mb_strlen($linea1)));
        $resto = preg_replace("/\n+/", " · ", $resto);

        return [$this->templateName, [
            'Alerta CRM Hawkins',
            now()->format('d/m/Y H:i'),
            mb_substr($linea1 ?: 'Incidencia detectada', 0, 60),
            mb_substr($resto ?: 'Revisar panel del CRM.', 0, 120),
        ]];
    }

    /**
     * Envia un template con N variables; si Meta rechaza (HTTP 400 o error
     * 131026/131047) lo registra para alerta interna sin dar error fatal.
     */
    private function sendAlertTemplate(string $phone, string $template, array $variables, string $originalMessage): void
    {
        $token = Setting::whatsappToken();
        $url   = Setting::whatsappUrl();

        $bodyParameters = array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], $variables);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $phone,
            'type'              => 'template',
            'template' => [
                'name'     => $template,
                'language' => ['code' => 'es'],
                'components' => [[
                    'type'       => 'body',
                    'parameters' => $bodyParameters,
                ]],
            ],
        ];

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::error('WhatsappNotificationService: error enviando template alerta', [
                'telefono' => $phone,
                'template' => $template,
                'status'   => $response->status(),
                'body'     => $response->body(),
            ]);
            return;
        }

        $wamid = $response->json('messages.0.id');
        if ($wamid) {
            try {
                \App\Models\WhatsappMensaje::create([
                    'mensaje_id'    => $wamid,
                    'tipo'          => 'template',
                    'contenido'     => "[{$template}] " . $originalMessage,
                    'remitente'     => 'SYSTEM',
                    'recipient_id'  => $wamid,
                    'fecha_mensaje' => now(),
                    'metadata'      => $payload,
                    'estado'        => 'accepted',
                ]);
            } catch (\Throwable $e) {
                Log::warning('WhatsappNotificationService: no se pudo persistir alerta template', [
                    'wamid' => $wamid, 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WhatsappNotificationService: template alerta enviado', [
            'telefono' => $phone,
            'template' => $template,
            'wamid'    => $wamid,
        ]);
    }

    /**
     * Enviar mensaje usando template de WhatsApp.
     *
     * @param string $phone
     * @param array{0:string,1:string,2:string,3:string} $variables
     */
    private function sendTemplate(string $phone, array $variables): void
    {
        $token = Setting::whatsappToken();

        Log::info('WhatsappNotificationService: enviando template', [
            'telefono' => $phone,
            'template' => $this->templateName,
            'variables' => $variables,
        ]);

        $bodyParameters = [];
        foreach ($variables as $value) {
            $bodyParameters[] = [
                'type' => 'text',
                'text' => $value,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $this->templateName,
                'language' => [
                    'code' => 'es',
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => $bodyParameters,
                    ],
                ],
            ],
        ];

        $url = Setting::whatsappUrl();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::error('WhatsappNotificationService: error enviando template', [
                'telefono' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Notificación interna (no se puede enviar WhatsApp sobre fallo de WhatsApp)
            try {
                \App\Models\Notification::createForAdmins(
                    \App\Models\Notification::TYPE_WHATSAPP,
                    'Error envío WhatsApp',
                    "No se pudo enviar WhatsApp template a {$phone}: HTTP {$response->status()}",
                    ['telefono' => $phone, 'status_code' => $response->status()],
                    \App\Models\Notification::PRIORITY_HIGH,
                    \App\Models\Notification::CATEGORY_ERROR
                );
            } catch (\Exception $notifEx) {
                Log::error('No se pudo crear notificación de fallo WhatsApp', ['error' => $notifEx->getMessage()]);
            }

            return;
        }

        $responseJson = $response->json();
        Storage::disk('local')->put(
            "overlap-whatsapp-template-{$phone}.json",
            json_encode($responseJson, JSON_PRETTY_PRINT)
        );

        $wamid = $responseJson['messages'][0]['id'] ?? null;

        if ($wamid) {
            try {
                \App\Models\WhatsappMensaje::create([
                    'mensaje_id'    => $wamid,
                    'tipo'          => 'template',
                    'contenido'     => $this->templateName . ': ' . implode(' | ', $variables),
                    'remitente'     => 'SYSTEM',
                    'recipient_id'  => $wamid,
                    'fecha_mensaje' => now(),
                    'metadata'      => $payload,
                    'estado'        => 'accepted',
                ]);
            } catch (\Throwable $e) {
                Log::warning('WhatsappNotificationService: no se pudo persistir template', [
                    'wamid' => $wamid, 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WhatsappNotificationService: template enviado correctamente', [
            'telefono' => $phone,
            'response_id' => $wamid,
        ]);
    }

    /**
     * Fallback: mensaje de texto simple (si el template no está disponible).
     */
    private function sendText(string $phone, string $message): void
    {
        $token = Setting::whatsappToken();

        Log::info('WhatsappNotificationService: enviando mensaje simple', [
            'telefono' => $phone,
            'preview' => mb_substr($message, 0, 120),
        ]);

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $phone,
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ];

        $url = Setting::whatsappUrl();

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $token,
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::error('WhatsappNotificationService: error enviando mensaje simple', [
                'telefono' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            // Notificación interna (no se puede enviar WhatsApp sobre fallo de WhatsApp)
            try {
                \App\Models\Notification::createForAdmins(
                    \App\Models\Notification::TYPE_WHATSAPP,
                    'Error envío WhatsApp',
                    "No se pudo enviar WhatsApp a {$phone}: HTTP {$response->status()}",
                    ['telefono' => $phone, 'status_code' => $response->status()],
                    \App\Models\Notification::PRIORITY_HIGH,
                    \App\Models\Notification::CATEGORY_ERROR
                );
            } catch (\Exception $notifEx) {
                Log::error('No se pudo crear notificación de fallo WhatsApp', ['error' => $notifEx->getMessage()]);
            }

            return;
        }

        $responseJson = $response->json();
        Storage::disk('local')->put(
            "overlap-whatsapp-text-{$phone}.json",
            json_encode($responseJson, JSON_PRETTY_PRINT)
        );

        $wamid = $responseJson['messages'][0]['id'] ?? null;

        // [2026-04-24] Guardar en whatsapp_mensajes con recipient_id = wamid
        // para que cuando Meta mande webhook de status (delivered/read/failed)
        // el procesarStatus() pueda encontrar la fila y actualizar el estado.
        // Antes los mensajes se enviaban pero nunca se registraban y todos
        // los webhooks de Meta aterrizaban huerfanos.
        if ($wamid) {
            try {
                \App\Models\WhatsappMensaje::create([
                    'mensaje_id'    => $wamid,
                    'tipo'          => 'text',
                    'contenido'     => $message,
                    'remitente'     => 'SYSTEM',
                    'recipient_id'  => $wamid,
                    'fecha_mensaje' => now(),
                    'metadata'      => $payload,
                    'estado'        => 'accepted',
                ]);
            } catch (\Throwable $e) {
                Log::warning('WhatsappNotificationService: no se pudo persistir mensaje', [
                    'wamid' => $wamid, 'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('WhatsappNotificationService: mensaje simple enviado correctamente', [
            'telefono' => $phone,
            'response_id' => $wamid,
        ]);
    }
}


