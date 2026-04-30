<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\WhatsappTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-27] Crea en Meta el template UTILITY `cambio_clave_emergencia`
 * en 7 idiomas (es, en, fr, de, it, pt_PT, ar). Sirve para avisar al
 * huesped cuando se activa el fallback de cerradura: el codigo unico que
 * recibio antes ya no funciona, debe usar el codigo de emergencia.
 *
 * Por que un template aprobado y no texto libre:
 *  - Meta bloquea texto libre fuera de la ventana 24h (error 131047).
 *  - Justo en una incidencia de cerradura el huesped puede no haber escrito
 *    al business -> el aviso no llegaba -> huesped se quedaba fuera.
 *  - Categoria UTILITY: aprobacion casi automatica (~30 min) y no cuenta
 *    como marketing.
 *
 * Parametros del body (5):
 *   {{1}} nombre del huesped
 *   {{2}} nombre del apartamento o suite
 *   {{3}} codigo nuevo (codigo_emergencia_portal)
 *   {{4}} fecha de entrada (dd/mm/yyyy)
 *   {{5}} fecha de salida (dd/mm/yyyy)
 *
 * Uso:
 *   php artisan whatsapp:crear-template-cambio-clave            -> envia los 7 a Meta
 *   php artisan whatsapp:crear-template-cambio-clave --solo=es  -> solo un idioma
 *   php artisan whatsapp:crear-template-cambio-clave --dry-run  -> muestra payload, no envia
 *
 * Tras la creacion, el cron whatsapp:sync-templates (cada 15 min) actualiza
 * el `status` de PENDING a APPROVED automaticamente.
 */
class WhatsappCrearTemplateCambioClave extends Command
{
    protected $signature = 'whatsapp:crear-template-cambio-clave
        {--solo= : crear solo el idioma indicado (ej: es)}
        {--dry-run : mostrar payload sin enviar}
        {--force : recrear aunque ya exista en BD}';

    protected $description = 'Crea en Meta el template UTILITY cambio_clave_emergencia para los 7 idiomas soportados';

    private const TEMPLATE_NAME = 'cambio_clave_emergencia';
    private const CATEGORY = 'UTILITY';

    public function handle(): int
    {
        $token = env('TOKEN_WHATSAPP') ?: Setting::whatsappToken();
        // El proyecto usa la env BUSINESS_ID (compat con el resto de comandos
        // de templates: WhatsappTemplateController, WhatsappSyncTemplates).
        $businessAccountId = env('BUSINESS_ID') ?: env('META_BUSINESS_ACCOUNT_ID');

        if (empty($token) || empty($businessAccountId)) {
            $this->error('Falta TOKEN_WHATSAPP o BUSINESS_ID en .env');
            return self::FAILURE;
        }

        $solo = $this->option('solo');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $idiomas = $this->definicionTemplates();
        if ($solo) {
            if (!isset($idiomas[$solo])) {
                $this->error("Idioma '{$solo}' no definido. Disponibles: " . implode(', ', array_keys($idiomas)));
                return self::FAILURE;
            }
            $idiomas = [$solo => $idiomas[$solo]];
        }

        foreach ($idiomas as $lang => $body) {
            $existente = WhatsappTemplate::where('name', self::TEMPLATE_NAME)
                ->where('language', $lang)
                ->first();
            if ($existente && !$force) {
                $this->line("· {$lang}: ya existe en BD ({$existente->status}), salto. Usa --force para recrear.");
                continue;
            }

            $payload = [
                'name' => self::TEMPLATE_NAME,
                'language' => $lang,
                'category' => self::CATEGORY,
                'components' => [
                    [
                        'type' => 'HEADER',
                        'format' => 'TEXT',
                        'text' => $body['header'],
                    ],
                    [
                        'type' => 'BODY',
                        'text' => $body['body'],
                        'example' => [
                            'body_text' => [[
                                'Juan', 'Suite 1A', '0001981', '01/05/2026', '03/05/2026',
                            ]],
                        ],
                    ],
                    [
                        'type' => 'FOOTER',
                        'text' => $body['footer'],
                    ],
                ],
            ];

            if ($dryRun) {
                $this->info("· {$lang} [dry-run]:");
                $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                continue;
            }

            $url = "https://graph.facebook.com/v19.0/{$businessAccountId}/message_templates";
            try {
                $resp = Http::withToken($token)->timeout(20)->post($url, $payload);
            } catch (\Throwable $e) {
                $this->error("· {$lang}: excepcion " . $e->getMessage());
                continue;
            }

            if ($resp->successful()) {
                $data = $resp->json();
                $this->info("· {$lang}: creado, id={$data['id']} status=" . ($data['status'] ?? 'PENDING'));
                WhatsappTemplate::updateOrCreate(
                    ['name' => self::TEMPLATE_NAME, 'language' => $lang],
                    [
                        'status' => $data['status'] ?? 'PENDING',
                        'category' => self::CATEGORY,
                        'components' => $payload['components'],
                        'meta_template_id' => $data['id'] ?? null,
                    ]
                );
            } else {
                $this->error("· {$lang}: HTTP {$resp->status()} -> " . mb_substr($resp->body(), 0, 200));
                Log::warning('[WaCrearCambioClave] Meta rechazo', [
                    'lang' => $lang, 'status' => $resp->status(), 'body' => $resp->body(),
                ]);
            }
        }

        $this->info('');
        $this->info('Cuando Meta apruebe (~30 min), el cron whatsapp:sync-templates marcara los templates como APPROVED.');
        return self::SUCCESS;
    }

    /**
     * Texto del template en cada idioma.
     *  Header: muy corto, llamada de atencion.
     *  Body: 5 parametros numericos {{1}}..{{5}}.
     *  Footer: marca y telefono de soporte.
     */
    private function definicionTemplates(): array
    {
        return [
            'es' => [
                'header' => 'Cambio de codigo de acceso',
                'body' => "Hola {{1}}, tenemos una incidencia con la cerradura del portal de {{2}}.\n\n"
                    . "El codigo que te enviamos antes YA NO FUNCIONA. Usa este nuevo codigo: *{{3}}* (pulsa # despues).\n\n"
                    . "Valido del {{4}} al {{5}}. Disculpa las molestias, ya estamos resolviendolo.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'en' => [
                'header' => 'Access code change',
                'body' => "Hi {{1}}, there is an issue with the front door lock at {{2}}.\n\n"
                    . "The code we sent you before NO LONGER WORKS. Please use this new code: *{{3}}* (press # after it).\n\n"
                    . "Valid from {{4}} to {{5}}. Sorry for the inconvenience, we are working on it.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'fr' => [
                'header' => 'Changement de code d acces',
                'body' => "Bonjour {{1}}, nous avons un probleme avec la serrure du hall de {{2}}.\n\n"
                    . "Le code que nous vous avions envoye NE FONCTIONNE PLUS. Utilisez ce nouveau code: *{{3}}* (appuyez # ensuite).\n\n"
                    . "Valable du {{4}} au {{5}}. Desole pour la gene occasionnee, nous resolvons l incident.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'de' => [
                'header' => 'Zugangscode geandert',
                'body' => "Hallo {{1}}, es gibt ein Problem mit dem Tuerschloss des Eingangs von {{2}}.\n\n"
                    . "Der Code, den wir Ihnen zuvor gesendet haben, FUNKTIONIERT NICHT MEHR. Bitte verwenden Sie diesen neuen Code: *{{3}}* (anschliessend # druecken).\n\n"
                    . "Gultig vom {{4}} bis zum {{5}}. Entschuldigen Sie die Unannehmlichkeiten.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'it' => [
                'header' => 'Cambio codice di accesso',
                'body' => "Ciao {{1}}, abbiamo un problema con la serratura del portone di {{2}}.\n\n"
                    . "Il codice che ti abbiamo inviato prima NON FUNZIONA PIU. Usa questo nuovo codice: *{{3}}* (premi # dopo).\n\n"
                    . "Valido dal {{4}} al {{5}}. Ci scusiamo per il disagio, stiamo risolvendo.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'pt_PT' => [
                'header' => 'Alteracao do codigo de acesso',
                'body' => "Ola {{1}}, temos um problema com a fechadura do portao de {{2}}.\n\n"
                    . "O codigo que enviamos antes JA NAO FUNCIONA. Por favor use este novo codigo: *{{3}}* (carregue em # depois).\n\n"
                    . "Valido de {{4}} a {{5}}. Pedimos desculpa pelo incomodo, estamos a resolver.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
            'ar' => [
                'header' => 'تغيير رمز الدخول',
                'body' => "مرحبا {{1}}, لدينا مشكلة في قفل باب المدخل في {{2}}.\n\n"
                    . "الرمز الذي ارسلناه لك سابقا لم يعد يعمل. الرجاء استخدام هذا الرمز الجديد: *{{3}}* (اضغط على # بعده).\n\n"
                    . "صالح من {{4}} الى {{5}}. نعتذر عن الازعاج, نحن نعمل على الحل.",
                'footer' => 'Hawkins Suites - Apartamentos Algeciras',
            ],
        ];
    }
}
