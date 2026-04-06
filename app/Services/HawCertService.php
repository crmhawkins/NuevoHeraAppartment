<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HawCertService
{
    protected string $baseUrl = 'https://hawcert.hawkins.es';

    protected string $serviceSlug = 'crm-apartamentos';

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Validar certificado por certificate_key + service_slug.
     * Devuelve array con success, access_token, user, certificate, permissions o error.
     */
    public function validateCertificate(string $certificateKey, ?string $serviceSlug = null): array
    {
        $slug = $serviceSlug ?? $this->serviceSlug;
        $url = $this->baseUrl . '/api/validate-certificate';

        Log::info('HawCert validate-certificate request', [
            'endpoint' => $url,
            'service_slug' => $slug,
            'certificate_key_length' => strlen($certificateKey),
        ]);

        $response = Http::timeout(15)
            ->acceptJson()
            ->post($url, [
                'certificate_key' => $certificateKey,
                'service_slug' => $slug,
            ]);

        $body = $response->json();
        $status = $response->status();

        Log::info('HawCert validate-certificate response', [
            'http_status' => $status,
            'body' => $body,
        ]);

        if ($status !== 200) {
            Log::warning('HawCert validate-certificate HTTP error', [
                'status' => $status,
                'body' => $body,
                'message' => $body['message'] ?? null,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Error al validar el certificado',
                'http_status' => $status,
            ];
        }

        if (empty($body['success']) || $body['success'] !== true) {
            Log::warning('HawCert validate-certificate success=false', [
                'body_success' => $body['success'] ?? null,
                'body_message' => $body['message'] ?? null,
                'full_body' => $body,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Certificado no válido',
                'http_status' => $status,
            ];
        }

        return [
            'success' => true,
            'access_token' => $body['access_token'] ?? null,
            'expires_at' => $body['expires_at'] ?? null,
            'user' => $body['user'] ?? [],
            'certificate' => $body['certificate'] ?? [],
            'permissions' => $body['permissions'] ?? [],
        ];
    }

    /**
     * Validar acceso con certificado PEM y obtener access key (un solo uso).
     * Lo usa el cliente; la plataforma luego consume la key con validateKey().
     */
    public function validateAccess(string $certificatePem, string $url, ?string $serviceSlug = null): array
    {
        $slug = $serviceSlug ?? $this->serviceSlug;
        $endpoint = $this->baseUrl . '/api/validate-access';

        $payload = [
            'certificate' => $certificatePem,
            'url' => $url,
        ];
        if ($slug) {
            $payload['service_slug'] = $slug;
        }

        Log::info('HawCert validate-access request', [
            'endpoint' => $endpoint,
            'url' => $url,
            'service_slug' => $slug,
            'certificate_length' => strlen($certificatePem),
            'certificate_preview' => substr(trim($certificatePem), 0, 50) . '...',
        ]);

        $response = Http::timeout(15)
            ->acceptJson()
            ->post($endpoint, $payload);

        $body = $response->json();
        $status = $response->status();
        $responseBodyRaw = $response->body();

        Log::info('HawCert validate-access response', [
            'http_status' => $status,
            'body' => $body,
            'raw_body_preview' => strlen($responseBodyRaw) > 500 ? substr($responseBodyRaw, 0, 500) . '...' : $responseBodyRaw,
        ]);

        if ($status !== 200) {
            Log::warning('HawCert validate-access HTTP error', [
                'status' => $status,
                'body' => $body,
                'message' => $body['message'] ?? null,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Error al validar el acceso',
                'http_status' => $status,
            ];
        }

        if (empty($body['success']) || $body['success'] !== true) {
            Log::warning('HawCert validate-access success=false en body', [
                'body_success' => $body['success'] ?? null,
                'body_message' => $body['message'] ?? null,
                'full_body' => $body,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Acceso denegado',
                'http_status' => $status,
            ];
        }

        return [
            'success' => true,
            'access_key' => $body['access_key'] ?? null,
            'expires_at' => $body['expires_at'] ?? null,
            'service' => $body['service'] ?? [],
            'user' => $body['user'] ?? [],
            'certificate' => $body['certificate'] ?? [],
            'permissions' => $body['permissions'] ?? [],
        ];
    }

    /**
     * Validar/consumir la access key (un solo uso).
     * La URL debe tener el mismo host que la usada al generar la key en validate-access.
     */
    public function validateKey(string $key, string $url): array
    {
        $endpoint = $this->baseUrl . '/api/validate-key';

        Log::info('HawCert validate-key request', [
            'endpoint' => $endpoint,
            'url' => $url,
            'key_length' => strlen($key),
            'key_prefix' => substr($key, 0, 8) . '...',
        ]);

        $response = Http::timeout(15)
            ->acceptJson()
            ->post($endpoint, [
                'key' => $key,
                'url' => $url,
            ]);

        $body = $response->json();
        $status = $response->status();

        Log::info('HawCert validate-key response', [
            'http_status' => $status,
            'body' => $body,
        ]);

        if ($status !== 200) {
            Log::warning('HawCert validate-key HTTP error', [
                'status' => $status,
                'body' => $body,
                'message' => $body['message'] ?? null,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Error al validar la clave de acceso',
                'http_status' => $status,
            ];
        }

        if (empty($body['success']) || empty($body['valid'])) {
            Log::warning('HawCert validate-key success/valid false', [
                'body_success' => $body['success'] ?? null,
                'body_valid' => $body['valid'] ?? null,
                'body_message' => $body['message'] ?? null,
                'full_body' => $body,
            ]);
            return [
                'success' => false,
                'message' => $body['message'] ?? 'Clave inválida o ya utilizada',
                'http_status' => $status,
            ];
        }

        return [
            'success' => true,
            'valid' => true,
            'certificate' => $body['certificate'] ?? [],
            'user' => $body['user'] ?? [],
            'service' => $body['service'] ?? [],
            'permissions' => $body['permissions'] ?? [],
            'expires_at' => $body['expires_at'] ?? null,
        ];
    }

    /**
     * Obtener el identificador efectivo (email) del usuario desde una respuesta de validación.
     */
    public static function getEffectiveEmail(array $result): ?string
    {
        $user = $result['user'] ?? [];
        $cert = $result['certificate'] ?? [];
        return $user['email'] ?? $cert['email'] ?? null;
    }
}
