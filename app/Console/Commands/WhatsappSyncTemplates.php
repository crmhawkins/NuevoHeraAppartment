<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\WhatsappTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-24] Sincroniza el estado de TODOS los templates de WhatsApp
 * entre Meta Business y la BD local.
 *
 * Util porque:
 *  - Cuando creamos un template (p.ej. dni_dia_entrada en 7 idiomas) Meta
 *    tarda minutos a horas en aprobarlo y pasa de PENDING a APPROVED.
 *  - El codigo del CRM decide que template usar segun el status en BD.
 *  - Sin este sync, aunque Meta aprobara, la BD seguiria mostrando PENDING
 *    y no se usaria nunca. Teniamos que tocar BD a mano.
 *
 * Se ejecuta cada 15 min (ver Kernel.php schedule). Tambien puede
 * lanzarse a demanda: php artisan whatsapp:sync-templates
 */
class WhatsappSyncTemplates extends Command
{
    protected $signature = 'whatsapp:sync-templates {--verbose-output : listar cada cambio}';
    protected $description = 'Sincroniza el estado de los templates WhatsApp desde Meta Business API a la BD local.';

    public function handle(): int
    {
        $token = Setting::whatsappToken();
        $businessId = env('BUSINESS_ID');
        if (!$token || !$businessId) {
            $this->error('Falta whatsapp_token o BUSINESS_ID');
            return self::FAILURE;
        }

        $cambios = 0;
        $total = 0;
        $errores = 0;

        // Paginamos por si hay muchos templates.
        $url = "https://graph.facebook.com/v19.0/{$businessId}/message_templates?fields=name,language,status,category,components,id&limit=100";
        while ($url) {
            try {
                $r = Http::withToken($token)->timeout(30)->get($url);
                if (!$r->successful()) {
                    Log::warning('[whatsapp:sync-templates] HTTP ' . $r->status() . ': ' . mb_substr($r->body(), 0, 300));
                    $this->error('Meta HTTP ' . $r->status());
                    return self::FAILURE;
                }
                $data = $r->json();
                foreach ((array) ($data['data'] ?? []) as $t) {
                    $total++;
                    $name = $t['name'] ?? null;
                    $lang = $t['language'] ?? null;
                    if (!$name || !$lang) continue;

                    $local = WhatsappTemplate::where('name', $name)->where('language', $lang)->first();
                    $nuevoStatus = $t['status'] ?? 'PENDING';

                    if (!$local) {
                        // No la tenemos en BD, la creamos
                        WhatsappTemplate::create([
                            'name'        => $name,
                            'language'    => $lang,
                            'template_id' => $t['id'] ?? null,
                            'category'    => $t['category'] ?? 'UTILITY',
                            'components'  => $t['components'] ?? [],
                            'status'      => $nuevoStatus,
                        ]);
                        $cambios++;
                        if ($this->option('verbose-output')) {
                            $this->line(" [NEW] {$name} ({$lang}) -> {$nuevoStatus}");
                        }
                        Log::info('[whatsapp:sync-templates] Nuevo template en BD', [
                            'name' => $name, 'language' => $lang, 'status' => $nuevoStatus,
                        ]);
                    } elseif ($local->status !== $nuevoStatus) {
                        $antes = $local->status;
                        $local->update([
                            'status'      => $nuevoStatus,
                            'template_id' => $t['id'] ?? $local->template_id,
                            'components'  => $t['components'] ?? $local->components,
                        ]);
                        $cambios++;
                        if ($this->option('verbose-output')) {
                            $this->info(" [CHANGE] {$name} ({$lang}): {$antes} -> {$nuevoStatus}");
                        }
                        Log::info('[whatsapp:sync-templates] Template status cambiado', [
                            'name'   => $name,
                            'language' => $lang,
                            'antes'  => $antes,
                            'ahora'  => $nuevoStatus,
                        ]);
                    }
                }
                // Siguiente pagina si existe
                $url = $data['paging']['next'] ?? null;
            } catch (\Throwable $e) {
                $errores++;
                Log::warning('[whatsapp:sync-templates] Excepcion', ['error' => $e->getMessage()]);
                $this->error('Excepcion: ' . $e->getMessage());
                break;
            }
        }

        $this->info("Sincronizacion completada: total={$total} cambios={$cambios} errores={$errores}");
        return $errores === 0 ? self::SUCCESS : self::FAILURE;
    }
}
