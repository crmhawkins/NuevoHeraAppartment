<?php

namespace App\Console\Commands;

use App\Services\WhatsappNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-04-22] Health-check de los endpoints de IA que usa el CRM.
 *
 * Comprueba:
 *  - Wrapper Python en :11435 (endpoint /chat/chat, usado por WhatsApp
 *    y por el AIGatewayService como fallback de OpenAI).
 *  - API nativa Ollama en :11434 (endpoint /api/tags, lista los modelos
 *    cargados). Permite verificar que los modelos esperados existen.
 *  - Inferencia real con gpt-oss:120b-cloud y qwen3-vl:8b para detectar
 *    degradaciones silenciosas (modelo cargado pero no responde).
 *
 * Si algo falla, manda WhatsApp a los admins configurados en
 * WhatsappNotificationService::sendToConfiguredRecipients. Para evitar
 * spam cuando la IA lleva caída un rato, solo avisa UNA VEZ cada 30 min
 * por tipo de fallo (controlado por Cache).
 *
 * Se ejecuta cada 5 min desde Kernel.php.
 */
class CheckIaHealth extends Command
{
    protected $signature = 'ia:healthcheck {--verbose-output : mostrar detalles de cada check}';
    protected $description = 'Verifica que los endpoints de IA (Ollama + wrapper) y los modelos esperados respondan. Avisa por WhatsApp si algo esta caido.';

    // Una alerta por tipo de fallo cada 30 min — si la IA esta caida no queremos
    // 6 mensajes/hora al admin.
    private const ALERT_TTL_MINUTES = 30;

    // Modelos que el CRM espera encontrar en Ollama. Si falta alguno, alerta.
    // [2026-04-26] Anadido qwen3-vl:235b-cloud — fallback de OCR cuando el
    // modelo local no extrae bien el numero_soporte_documento del DNI.
    // Si este modelo cae o el plan Ollama Cloud se queda sin cuota, las
    // reservas con DNI espanol se quedaran sin numero de soporte y bloqueadas
    // para MIR. Es critico vigilarlo.
    private const MODELOS_ESPERADOS = [
        'qwen3-vl:8b',           // OCR DNI/pasaporte/facturas (local)
        'gpt-oss:120b-cloud',    // chat WhatsApp/Channex/emails + lookup CP
        'qwen3-vl:235b-cloud',   // fallback OCR DNI (numero_soporte_documento)
    ];

    public function handle(): int
    {
        $fallos = [];

        // [2026-04-29] 0) Cache de Laravel funcional. Si el cache esta roto
        // (permisos, disk full, redis caido), CASI TODO el flujo del CRM
        // se rompe silenciosamente: scheduler, WhatsappController, sesiones,
        // healthcheck mismo. Detectarlo rapido es vital.
        //
        // Doble verificacion:
        //  a) Recorrer TODOS los subdirs reales de storage/framework/cache/data
        //     y bootstrap/cache, comprobar que son escribibles. Asi cazamos
        //     subdirs con permisos rotos aunque ningun canary caiga ahi.
        //  b) Canary: escribir y leer un valor por Cache::* para verificar
        //     que el driver responde end-to-end.
        try {
            // (a) Recorrido de directorios
            $dirsACheckear = [
                storage_path('framework/cache/data'),
                base_path('bootstrap/cache'),
            ];
            foreach ($dirsACheckear as $base) {
                if (!is_dir($base)) continue;
                if (!is_writable($base)) {
                    $fallos[] = "Cache: directorio raiz no es escribible: {$base}";
                    break;
                }
                // Comprobar subdirs: si alguno no es escribible por el usuario actual
                $iter = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($base, \FilesystemIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
                $rotos = [];
                foreach ($iter as $info) {
                    if ($info->isDir() && !$info->isWritable()) {
                        $rotos[] = $info->getPathname();
                        if (count($rotos) >= 3) break; // primeros 3 ejemplos
                    }
                }
                if (!empty($rotos)) {
                    $fallos[] = "Cache: subdirs sin permisos de escritura: " . implode(', ', $rotos);
                    break;
                }
            }

            // (b) Canary write/read
            $canary = 'ia_healthcheck_canary_' . random_int(100000, 999999);
            $valor = (string) microtime(true);
            \Illuminate\Support\Facades\Cache::put($canary, $valor, 60);
            $leido = \Illuminate\Support\Facades\Cache::get($canary);
            \Illuminate\Support\Facades\Cache::forget($canary);
            if ($leido !== $valor) {
                $fallos[] = "Cache de Laravel: escritura/lectura no coincide (escrito='{$valor}', leido='{$leido}').";
            }
        } catch (\Throwable $e) {
            $fallos[] = "Cache de Laravel: excepcion — " . mb_substr($e->getMessage(), 0, 200);
        }

        // 1) Wrapper 11435 (texto) — lo usan WhatsappController, WebhookController
        //    y el AIGatewayService como fallback de OpenAI.
        $wrapperUrl = rtrim((string) config('services.hawkins_ai.url', env('HAWKINS_AI_URL', '')), '/');
        $wrapperKey = (string) config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));
        if ($wrapperUrl === '' || $wrapperKey === '') {
            $fallos[] = "Config IA incompleta: HAWKINS_AI_URL o HAWKINS_AI_API_KEY vacios.";
        } else {
            $wrapperChatUrl = str_ends_with($wrapperUrl, '/chat/chat')
                ? $wrapperUrl
                : $wrapperUrl . '/chat/chat';
            $fallo = $this->probarWrapper($wrapperChatUrl, $wrapperKey);
            if ($fallo !== null) {
                $fallos[] = "Wrapper IA ({$wrapperChatUrl}): {$fallo}";
            }
        }

        // 2) API nativa Ollama :11434 — la usan DNIScannerController y
        //    CheckInPublicController para OCR. Si :11435 esta configurado,
        //    derivamos :11434 igual que hace el codigo de produccion.
        $ollamaUrl = $wrapperUrl !== ''
            ? preg_replace('/:11435(\/)?$/', ':11434$1', $wrapperUrl)
            : '';
        if ($ollamaUrl !== '') {
            $modelosEncontrados = $this->listarModelosOllama($ollamaUrl);
            if ($modelosEncontrados === null) {
                $fallos[] = "Ollama ({$ollamaUrl}/api/tags): no responde.";
            } else {
                foreach (self::MODELOS_ESPERADOS as $modeloEsperado) {
                    if (!in_array($modeloEsperado, $modelosEncontrados, true)) {
                        $fallos[] = "Modelo IA esperado no instalado: {$modeloEsperado}. Cargados: " . implode(', ', $modelosEncontrados);
                    }
                }

                // 3) Inferencia real end-to-end. Cargado != responde. Un
                //    prompt barato detecta degradaciones del runtime o el
                //    tunel a Ollama Cloud (cuota agotada, red, etc).
                //    [2026-04-26] qwen3-vl:235b-cloud queda fuera de la
                //    inferencia recurrente porque al ser modelo de vision
                //    consume mucho mas tokens por llamada. Verificamos su
                //    presencia en /api/tags (suficiente para detectar caida
                //    del tunel o del plan cloud) y confiamos en que el
                //    healthcheck de gpt-oss:120b-cloud detecte la salud
                //    general de Ollama Cloud.
                $modelosConInferencia = array_diff(self::MODELOS_ESPERADOS, [
                    'qwen3-vl:235b-cloud',
                ]);
                foreach ($modelosConInferencia as $modelo) {
                    if (!in_array($modelo, $modelosEncontrados, true)) continue;
                    $fallo = $this->probarInferencia($ollamaUrl, $modelo);
                    if ($fallo !== null) {
                        $fallos[] = "Modelo {$modelo}: {$fallo}";
                    }
                }
            }
        }

        // Output para el admin que corre el comando a mano
        if ($this->option('verbose-output') || empty($fallos)) {
            $this->info(empty($fallos) ? '✓ Todo OK' : '✗ Fallos detectados:');
            foreach ($fallos as $f) $this->line(' - ' . $f);
        }

        if (empty($fallos)) {
            // Si habia una alerta activa y ahora va todo, mandamos WhatsApp de
            // "recuperado" (una sola vez) y limpiamos la cache.
            if (Cache::has('ia_health_alert_active')) {
                $this->notificarWhatsapp("✅ IA RECUPERADA\n\nTodos los endpoints y modelos responden correctamente.");
                Cache::forget('ia_health_alert_active');
                Cache::forget('ia_health_alert_last_fails');
            }
            // Limpia contador de fallos consecutivos
            Cache::forget('ia_health_consecutive_fails');
            return self::SUCCESS;
        }

        // Hay fallos. Consolidar y alertar, PERO ahora con grace period de
        // 2 ciclos: el autoheal del VPS + watchdog del 5090 suelen recuperar
        // en <2 min, asi que el primer fallo puede ser ruido.
        Log::warning('[ia:healthcheck] Fallos detectados', ['fallos' => $fallos]);

        $consecutivos = (int) (Cache::get('ia_health_consecutive_fails', 0)) + 1;
        Cache::put('ia_health_consecutive_fails', $consecutivos, now()->addHours(6));

        if ($consecutivos < 2) {
            Log::info('[ia:healthcheck] Primer fallo — dando margen al autoheal antes de alertar', [
                'consecutivos' => $consecutivos,
            ]);
            return self::FAILURE;
        }

        $cacheKey = 'ia_health_alert_sent_' . md5(implode('|', $fallos));
        if (!Cache::has($cacheKey)) {
            $texto = "⚠️ IA NO DISPONIBLE\n\n";
            foreach ($fallos as $f) {
                $texto .= "• {$f}\n";
            }
            $texto .= "\nRevisa scheduled tasks en el 5090 (OllamaServe, TunnelIA5090, AIWrapper) o el tunel autossh del VPS.";
            $this->notificarWhatsapp($texto);

            Cache::put($cacheKey, true, now()->addMinutes(self::ALERT_TTL_MINUTES));
            Cache::put('ia_health_alert_active', true, now()->addHours(6));
            Cache::put('ia_health_alert_last_fails', $fallos, now()->addHours(6));
        }

        return self::FAILURE;
    }

    /**
     * Prueba el endpoint /chat/chat del wrapper con un prompt minimo.
     * Devuelve null si OK, descripcion del fallo si KO.
     */
    private function probarWrapper(string $url, string $apiKey): ?string
    {
        try {
            $resp = Http::withHeaders([
                'X-API-Key'    => $apiKey,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->withoutVerifying()
            ->post($url, [
                'prompt' => 'ping',
                'modelo' => 'gpt-oss:120b-cloud',
            ]);

            if (!$resp->successful()) {
                return "HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 150);
            }
            $body = $resp->json();
            $texto = $body['response'] ?? $body['respuesta'] ?? $body['message'] ?? null;
            if (empty($texto)) {
                return "respuesta vacia: " . mb_substr((string) $resp->body(), 0, 150);
            }
            return null;
        } catch (\Throwable $e) {
            return "excepcion: " . mb_substr($e->getMessage(), 0, 150);
        }
    }

    /**
     * @return array<int, string>|null Lista de nombres de modelo o null si /api/tags no responde.
     */
    private function listarModelosOllama(string $ollamaBaseUrl): ?array
    {
        try {
            $resp = Http::timeout(10)->withoutVerifying()->get(rtrim($ollamaBaseUrl, '/') . '/api/tags');
            if (!$resp->successful()) return null;
            $data = $resp->json();
            $models = $data['models'] ?? [];
            return array_values(array_filter(array_map(
                fn($m) => $m['name'] ?? $m['model'] ?? null,
                is_array($models) ? $models : []
            )));
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Ejecuta un prompt minimo contra un modelo concreto via /api/chat.
     * Devuelve null si el modelo responde con al menos 1 token, descripcion si no.
     */
    private function probarInferencia(string $ollamaBaseUrl, string $modelo): ?string
    {
        try {
            $resp = Http::timeout(45)
                ->withoutVerifying()
                ->post(rtrim($ollamaBaseUrl, '/') . '/api/chat', [
                    'model'    => $modelo,
                    'messages' => [['role' => 'user', 'content' => 'ping']],
                    'stream'   => false,
                    'options'  => ['num_predict' => 5, 'temperature' => 0.1],
                ]);

            if (!$resp->successful()) {
                return "HTTP {$resp->status()}: " . mb_substr($resp->body(), 0, 120);
            }
            $data = $resp->json();
            $content = $data['message']['content'] ?? '';
            $evalCount = $data['eval_count'] ?? 0;

            // Aceptamos eval_count >= 1 aunque content este vacio (hay modelos
            // que responden con whitespace a "ping"). Solo fallamos si no genero
            // absolutamente nada.
            if ($evalCount < 1 && $content === '') {
                return "no genero tokens: " . mb_substr((string) $resp->body(), 0, 120);
            }
            return null;
        } catch (\Throwable $e) {
            return "excepcion: " . mb_substr($e->getMessage(), 0, 150);
        }
    }

    private function notificarWhatsapp(string $texto): void
    {
        try {
            app(WhatsappNotificationService::class)->sendToConfiguredRecipients($texto);
        } catch (\Throwable $e) {
            Log::error('[ia:healthcheck] No se pudo enviar WhatsApp: ' . $e->getMessage());
        }
    }
}
