<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI;

/**
 * AIGatewayService
 *
 * Gateway unificado para peticiones de chat/completion a modelos de IA.
 * Intenta primero con OpenAI (openai-php/client). Si OpenAI falla por
 * cualquier motivo (quota agotada, timeout, 5xx, red, excepcion),
 * reintenta automaticamente con Hawkins AI (aiapi.hawkins.es/chat/chat)
 * usando el modelo gpt-oss:120b-cloud o el configurado en settings.
 *
 * CIRCUIT BREAKER
 * ---------------
 * Si OpenAI falla N veces seguidas, durante COOLDOWN segundos las
 * siguientes peticiones van DIRECTAS a Hawkins sin tocar OpenAI. Asi se
 * evita quemar cuota / tiempo / logs cuando se sabe que OpenAI esta caido.
 * Tras COOLDOWN el circuito se cierra y se vuelve a probar OpenAI.
 *
 * USO
 * ---
 *     $response = app(\App\Services\AIGatewayService::class)->chatCompletion([
 *         'model' => 'gpt-4',
 *         'messages' => [['role' => 'user', 'content' => 'Hola']],
 *         'max_tokens' => 100,
 *     ]);
 *     $texto = $response['choices'][0]['message']['content'];
 *
 * La respuesta tiene siempre el mismo formato que OpenAI, de modo que el
 * codigo llamador no necesita saber que motor respondio (OpenAI o Hawkins).
 * Se anade una clave interna '_gateway_source' con el valor 'openai' o
 * 'hawkins' para debug.
 */
class AIGatewayService
{
    private const CB_KEY = 'ai_gateway_openai_circuit';
    private const CB_FAIL_THRESHOLD = 3;
    private const CB_COOLDOWN_SECONDS = 600; // 10 min

    /**
     * Hace una peticion de chat completion en formato compatible OpenAI.
     *
     * @param array $params Parametros estandar OpenAI: model, messages,
     *                      max_tokens, temperature, n, tools, etc.
     * @return array Respuesta normalizada al formato OpenAI:
     *               ['choices' => [['message' => ['content' => '...']]], ...]
     * @throws \RuntimeException Si ambos motores fallan.
     */
    public function chatCompletion(array $params): array
    {
        $openaiKey = env('OPENAI_API_KEY');

        // Si OpenAI esta en circuit-break o no tiene key, saltar directo a Hawkins
        $skipOpenAI = $this->isCircuitOpen() || empty($openaiKey);

        if (!$skipOpenAI) {
            try {
                $openai = OpenAI::client($openaiKey);
                $response = $openai->chat()->create($params);
                $this->circuitSuccess();
                return $this->normalizeOpenAIResponse($response);
            } catch (\Throwable $e) {
                $msg = $e->getMessage();
                $esQuota = stripos($msg, 'quota') !== false || stripos($msg, 'insufficient') !== false;
                Log::warning('[AIGateway] OpenAI fallo, reintentando con Hawkins AI', [
                    'error' => mb_substr($msg, 0, 250),
                    'quota_issue' => $esQuota,
                ]);
                $this->circuitFailure($msg);
                // Continuamos al fallback
            }
        } else {
            if ($this->isCircuitOpen()) {
                Log::debug('[AIGateway] Circuit breaker OpenAI OPEN - saltando a Hawkins');
            }
        }

        // Fallback a Hawkins AI
        try {
            return $this->hawkinsCompletion($params);
        } catch (\Throwable $e) {
            Log::error('[AIGateway] Hawkins AI tambien fallo', [
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException(
                'Todos los motores de IA fallaron. Ultimo error: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Envia una peticion a Hawkins AI traduciendo los messages al formato prompt plano.
     * Hawkins AI espera { prompt: string, modelo: string } y devuelve { response: string }.
     */
    private function hawkinsCompletion(array $params): array
    {
        $baseUrl = config('services.hawkins_ai.url', env('HAWKINS_AI_URL', 'https://aiapi.hawkins.es/'));
        $apiKey  = config('services.hawkins_ai.api_key', env('HAWKINS_AI_API_KEY'));

        // Modelo de fallback para TEXTO (distinto del de vision que usa el
        // resto del CRM para DNI/facturas). Usamos qwen3:latest por defecto
        // porque es local, rapido y suficiente para clasificacion y respuesta
        // de emails. Se puede sobrescribir via env HAWKINS_AI_CHAT_MODEL o
        // pasando 'hawkins_model' en los parametros de la llamada.
        $modelo = $params['hawkins_model']
            ?? env('HAWKINS_AI_CHAT_MODEL', 'qwen3:latest');

        if (empty($apiKey)) {
            throw new \RuntimeException('Hawkins AI no configurada (falta HAWKINS_AI_API_KEY)');
        }

        // Construir URL /chat/chat a partir de la base URL
        $url = rtrim($baseUrl, '/');
        if (!str_ends_with($url, '/chat/chat')) {
            if (str_ends_with($url, '/chat')) {
                $url .= '/chat';
            } else {
                $url .= '/chat/chat';
            }
        }

        $prompt = $this->messagesToPrompt($params['messages'] ?? []);

        // Hawkins AI usa certificados que pueden estar caducados o ser FNMT,
        // desactivamos la verificacion SSL igual que hacen DNIScannerController
        // y FacturaScannerService.
        $httpClient = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])
        ->timeout(60)
        ->withoutVerifying();

        $response = $httpClient->post($url, [
            'prompt' => $prompt,
            'modelo' => $modelo,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("Hawkins AI HTTP {$response->status()}: " . mb_substr($response->body(), 0, 250));
        }

        $body = $response->json();
        // El endpoint puede devolver el texto en distintos campos segun version
        $texto = $body['response']
            ?? $body['respuesta']
            ?? $body['message']
            ?? $body['choices'][0]['message']['content']
            ?? null;

        if (is_array($texto)) {
            $texto = json_encode($texto, JSON_UNESCAPED_UNICODE);
        }
        if (!is_string($texto) || $texto === '') {
            throw new \RuntimeException('Hawkins AI respondio sin texto: ' . mb_substr(json_encode($body), 0, 250));
        }

        Log::info('[AIGateway] Respuesta Hawkins AI OK', [
            'modelo' => $modelo,
            'chars' => mb_strlen($texto),
        ]);

        // Devolver en formato compatible con OpenAI para que el codigo llamador
        // no tenga que distinguir entre motores.
        return [
            'id' => 'hawkins-' . uniqid(),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => $modelo,
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $texto,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 0,
                'completion_tokens' => 0,
                'total_tokens' => 0,
            ],
            '_gateway_source' => 'hawkins',
        ];
    }

    /**
     * Convierte el array de messages de OpenAI (con roles) en un prompt plano
     * que Hawkins AI puede procesar.
     */
    private function messagesToPrompt(array $messages): string
    {
        $parts = [];
        foreach ($messages as $m) {
            $role = $m['role'] ?? 'user';
            $content = $m['content'] ?? '';
            if (is_array($content)) {
                $content = json_encode($content, JSON_UNESCAPED_UNICODE);
            }
            $prefix = match ((string) $role) {
                'system'    => '[INSTRUCCIONES DEL SISTEMA]',
                'user'      => '[USUARIO]',
                'assistant' => '[ASISTENTE]',
                'tool'      => '[HERRAMIENTA]',
                'function'  => '[FUNCION]',
                default     => '[' . strtoupper((string) $role) . ']',
            };
            $parts[] = $prefix . "\n" . trim((string) $content);
        }
        return implode("\n\n", $parts);
    }

    /**
     * Normaliza la respuesta de openai-php/client a array plano.
     */
    private function normalizeOpenAIResponse($response): array
    {
        if (is_object($response) && method_exists($response, 'toArray')) {
            $arr = $response->toArray();
        } elseif (is_object($response)) {
            $arr = json_decode(json_encode($response), true) ?: [];
        } elseif (is_array($response)) {
            $arr = $response;
        } else {
            $arr = [];
        }
        $arr['_gateway_source'] = 'openai';
        return $arr;
    }

    // =========================================================================
    // Circuit breaker
    // =========================================================================

    private function isCircuitOpen(): bool
    {
        $data = Cache::get(self::CB_KEY);
        if (!is_array($data)) return false;
        return (int) ($data['failures'] ?? 0) >= self::CB_FAIL_THRESHOLD;
    }

    private function circuitFailure(string $error): void
    {
        $data = Cache::get(self::CB_KEY);
        if (!is_array($data)) $data = ['failures' => 0];
        $data['failures'] = ((int) ($data['failures'] ?? 0)) + 1;
        $data['last_error'] = mb_substr($error, 0, 250);
        $data['last_fail_at'] = now()->toIso8601String();
        Cache::put(self::CB_KEY, $data, self::CB_COOLDOWN_SECONDS);

        if ($data['failures'] === self::CB_FAIL_THRESHOLD) {
            Log::warning('[AIGateway] Circuit breaker ABIERTO - OpenAI se saltara durante ' . self::CB_COOLDOWN_SECONDS . 's');
        }
    }

    private function circuitSuccess(): void
    {
        Cache::forget(self::CB_KEY);
    }

    /**
     * Para debug: devuelve el estado del circuit breaker.
     */
    public function getCircuitStatus(): array
    {
        $data = Cache::get(self::CB_KEY, []);
        return [
            'open' => $this->isCircuitOpen(),
            'failures' => $data['failures'] ?? 0,
            'last_error' => $data['last_error'] ?? null,
            'last_fail_at' => $data['last_fail_at'] ?? null,
        ];
    }
}
