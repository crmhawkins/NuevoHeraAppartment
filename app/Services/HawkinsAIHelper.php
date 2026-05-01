<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * [2026-05-01] Helper centralizado para llamar a Hawkins AI con fallback
 * automatico cloud -> local.
 *
 * MOTIVACION:
 * Hawkins AI tiene dos modelos en produccion:
 *   - PRIMARIO (cloud)    : `gpt-oss:120b-cloud`  — calidad alta, rate limit
 *                           semanal Ollama Cloud (~10M tokens/semana).
 *   - SECUNDARIO (local)  : `gpt-oss:20b`         — corre en GPU 5090, sin
 *                           rate limit.
 *
 * Cuando el cloud agota cuota semanal, el wrapper IA devuelve HTTP 502 con
 * body `{"error":"ollama_http","status":429,"detail":"...weekly usage limit..."}`.
 * Este helper detecta ese patron y reintenta automaticamente con el modelo
 * local. Cuando el cloud recupera cuota, vuelve solo (siempre intenta
 * primero el primario).
 *
 * CRITICO: usar este helper en todos los flujos donde la IA NO PUEDE FALLAR
 * (atencion al huesped, envio de claves, OCR DNI). Antes habia 9 puntos
 * llamando directamente a Hawkins AI sin fallback y se cayo silencioso un
 * dia que el cloud agoto cuota.
 *
 * USO:
 *   $texto = HawkinsAIHelper::chat($prompt);                  // defaults
 *   $texto = HawkinsAIHelper::chat($prompt, $modeloPrimario);  // override
 */
class HawkinsAIHelper
{
    /**
     * Llama a Hawkins AI con fallback automatico cloud -> local.
     *
     * @param string $prompt          Prompt completo (texto plano).
     * @param string|null $modeloPrimario  Modelo cloud preferido. Si es null
     *                                     se lee de HAWKINS_AI_CHAT_MODEL.
     * @param string|null $modeloFallback  Modelo local de respaldo. Si es null
     *                                     se lee de HAWKINS_AI_CHAT_FALLBACK_MODEL.
     * @param int $timeoutSeconds          Timeout por intento (default 60).
     * @return string|null Respuesta de la IA, o null si AMBOS modelos fallaron.
     */
    public static function chat(
        string $prompt,
        ?string $modeloPrimario = null,
        ?string $modeloFallback = null,
        int $timeoutSeconds = 60
    ): ?string {
        $endpoint = self::resolverEndpoint();
        $apiKey = self::resolverApiKey();

        if (empty($endpoint) || empty($apiKey)) {
            Log::error('[HawkinsAI] Configuracion incompleta', [
                'tiene_endpoint' => !empty($endpoint),
                'tiene_apikey' => !empty($apiKey),
            ]);
            return null;
        }

        $modeloPrimario = $modeloPrimario
            ?? env('HAWKINS_AI_CHAT_MODEL')
            ?? env('HAWKINS_WHATSAPP_AI', 'gpt-oss:120b-cloud');
        $modeloFallback = $modeloFallback
            ?? env('HAWKINS_AI_CHAT_FALLBACK_MODEL', 'gpt-oss:20b');

        $http = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout($timeoutSeconds)->withoutVerifying();

        $modelo = $modeloPrimario;
        for ($intento = 1; $intento <= 2; $intento++) {
            try {
                $response = $http->post($endpoint, [
                    'prompt' => $prompt,
                    'modelo' => $modelo,
                ]);
            } catch (\Throwable $e) {
                Log::warning('[HawkinsAI] Excepcion en POST', [
                    'modelo' => $modelo,
                    'intento' => $intento,
                    'error' => $e->getMessage(),
                ]);
                // Si es el primer intento y hay modelo de fallback distinto, probar
                if ($intento === 1 && $modelo !== $modeloFallback) {
                    $modelo = $modeloFallback;
                    continue;
                }
                return null;
            }

            $bodyRaw = (string) $response->body();
            $body = $response->json();
            $status = $response->status();

            // Detectar limit cloud: 429 directo, o 502 con body que contiene
            // "weekly usage limit" / "weekly_limit" / "ollama_http".
            $esCloudLimit = false;
            if ($status === 429) {
                $esCloudLimit = true;
            } elseif ($status === 502 && is_array($body)) {
                $detail = (string) ($body['detail'] ?? '');
                $err = (string) ($body['error'] ?? '');
                if (
                    stripos($detail, 'weekly') !== false ||
                    stripos($detail, 'limit') !== false ||
                    stripos($err, 'ollama_http') !== false
                ) {
                    $esCloudLimit = true;
                }
            } elseif (
                stripos($bodyRaw, 'weekly usage limit') !== false ||
                stripos($bodyRaw, 'weekly_limit') !== false
            ) {
                $esCloudLimit = true;
            }

            if ($esCloudLimit && $intento === 1 && $modelo !== $modeloFallback) {
                Log::warning('[HawkinsAI] Cloud limit detectado, conmutando a LOCAL', [
                    'modelo_cloud' => $modelo,
                    'modelo_local' => $modeloFallback,
                    'status' => $status,
                    'body_preview' => mb_substr($bodyRaw, 0, 200),
                ]);
                $modelo = $modeloFallback;
                continue;
            }

            if ($response->failed()) {
                Log::error('[HawkinsAI] Error Hawkins AI', [
                    'modelo' => $modelo,
                    'status' => $status,
                    'body_preview' => mb_substr($bodyRaw, 0, 300),
                ]);
                return null;
            }

            $texto = is_array($body) ? ($body['respuesta'] ?? null) : null;
            if (empty($texto)) {
                Log::warning('[HawkinsAI] Respuesta sin campo respuesta', [
                    'modelo' => $modelo,
                    'data' => $body,
                ]);
                return null;
            }

            if ($intento === 2) {
                Log::info('[HawkinsAI] OK con fallback local', [
                    'modelo' => $modelo,
                    'chars' => strlen($texto),
                ]);
            }
            return $texto;
        }

        return null;
    }

    /**
     * Resuelve el endpoint /chat/chat de Hawkins AI desde la config.
     */
    private static function resolverEndpoint(): string
    {
        $base = config('services.hawkins_whatsapp_ai.base_url')
            ?: config('services.hawkins_ai.url')
            ?: env('HAWKINS_AI_URL', '');

        $base = rtrim($base, '/');
        if (empty($base)) {
            return '';
        }

        if (str_ends_with($base, '/chat/chat')) {
            return $base;
        }
        if (str_ends_with($base, '/chat')) {
            return $base . '/chat';
        }
        return $base . '/chat/chat';
    }

    /**
     * Resuelve la API key de Hawkins AI.
     */
    private static function resolverApiKey(): string
    {
        return config('services.hawkins_whatsapp_ai.api_key')
            ?: config('services.hawkins_ai.api_key')
            ?: env('HAWKINS_AI_API_KEY', '');
    }
}
