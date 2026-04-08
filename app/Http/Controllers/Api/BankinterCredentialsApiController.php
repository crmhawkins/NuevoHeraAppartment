<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankinterCredential;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * BankinterCredentialsApiController
 *
 * Endpoint seguro para que el PC externo (Windows) que ejecuta el scraper
 * Bankinter obtenga las credenciales actualizadas directamente desde el CRM,
 * evitando tener que mantener un .env sincronizado manualmente.
 *
 * El payload con las credenciales se cifra con AES-256-GCM (autenticado)
 * antes de devolverlo, usando una clave simetrica de 32 bytes compartida
 * con el cliente.
 *
 * Configuracion requerida en .env:
 *     BANKINTER_SCRAPER_API_TOKEN=<mismo token que import>
 *     BANKINTER_ENCRYPTION_KEY=<32 bytes en base64, compartida con el PC>
 *
 * Endpoint: GET /api/bankinter/scraper/credentials
 * Header:   X-Scraper-Token: <token>
 * Throttle: 30 peticiones/minuto/IP (en routes/api.php)
 */
class BankinterCredentialsApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $clientIp = $request->ip();

        // 1) Auth por token (timing-safe)
        $expectedToken = config('services.bankinter.scraper_api_token');
        $providedToken = (string) $request->header('X-Scraper-Token', '');

        if (empty($expectedToken) || !is_string($expectedToken)) {
            Log::error('[BankinterCredentialsApi] Token no configurado en el servidor', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!hash_equals($expectedToken, $providedToken)) {
            Log::warning('[BankinterCredentialsApi] Token invalido', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // 2) Validar clave de cifrado
        $keyB64 = config('services.bankinter.encryption_key');
        if (empty($keyB64)) {
            Log::error('[BankinterCredentialsApi] BANKINTER_ENCRYPTION_KEY no configurada', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Server misconfiguration'], 500);
        }

        $key = base64_decode($keyB64, true);
        if ($key === false || strlen($key) !== 32) {
            Log::error('[BankinterCredentialsApi] BANKINTER_ENCRYPTION_KEY invalida (debe ser 32 bytes base64)', [
                'ip' => $clientIp,
                'decoded_length' => $key === false ? 'invalid-base64' : strlen($key),
            ]);
            return response()->json(['error' => 'Server misconfiguration'], 500);
        }

        // 3) Cargar credenciales activas
        $credenciales = BankinterCredential::where('enabled', true)
            ->orderBy('alias')
            ->get();

        $cuentas = [];
        foreach ($credenciales as $cred) {
            $cuentas[] = [
                'alias' => $cred->alias,
                'label' => $cred->label,
                'user' => $cred->user,
                'password' => $cred->password, // decrypted by cast
                'iban' => $cred->iban,
                'bank_id' => $cred->bank_id,
            ];
        }

        $payload = [
            'generated_at' => now()->toIso8601String(),
            'count' => count($cuentas),
            'cuentas' => $cuentas,
        ];

        $plaintext = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($plaintext === false) {
            Log::error('[BankinterCredentialsApi] Error serializando payload', [
                'ip' => $clientIp,
            ]);
            return response()->json(['error' => 'Encoding error'], 500);
        }

        // 4) Cifrar con AES-256-GCM (autenticado)
        $iv = random_bytes(12); // GCM recomienda IV de 96 bits
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '', // aad vacio
            16  // tag de 16 bytes
        );

        if ($ciphertext === false) {
            Log::error('[BankinterCredentialsApi] Error cifrando payload', [
                'ip' => $clientIp,
                'openssl_error' => openssl_error_string(),
            ]);
            return response()->json(['error' => 'Encryption error'], 500);
        }

        // 5) Loguear solo metadata (NUNCA el contenido descifrado)
        Log::info('[BankinterCredentialsApi] Credenciales entregadas', [
            'ip' => $clientIp,
            'cuentas_count' => count($cuentas),
        ]);

        return response()->json([
            'format' => 'aes-256-gcm',
            'iv' => base64_encode($iv),
            'ciphertext' => base64_encode($ciphertext),
            'auth_tag' => base64_encode($tag),
        ]);
    }
}
