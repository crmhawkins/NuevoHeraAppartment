<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\HawCertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HawCertAuthController extends Controller
{
    public function __construct(
        protected HawCertService $hawCert
    ) {}

    /**
     * Login con certificado PEM (archivo subido).
     * Valida con HawCert validate-access, obtiene access_key, llama validate-key y hace login.
     * POST /auth/hawcert/login-with-certificate
     */
    public function loginWithCertificate(Request $request)
    {
        $request->validate([
            'certificate' => 'required|file|max:10240',
        ], [
            'certificate.required' => 'Seleccione un archivo de certificado.',
        ]);

        $pem = $request->file('certificate')->get();
        if (empty(trim($pem))) {
            throw ValidationException::withMessages([
                'certificate' => ['El archivo del certificado está vacío.'],
            ]);
        }

        $url = $request->root();
        Log::info('HawCert loginWithCertificate: inicio', [
            'url_enviada' => $url,
            'request_url' => $request->fullUrl(),
            'request_scheme_and_host' => $request->getScheme() . '://' . $request->getHttpHost(),
            'certificate_file_size' => strlen($pem),
        ]);

        $resultAccess = $this->hawCert->validateAccess($pem, $url);

        if (!$resultAccess['success']) {
            $message = $resultAccess['message'] ?? 'Certificado inválido o sin acceso al servicio.';
            if (($resultAccess['http_status'] ?? 0) === 403 && str_contains($message, 'no tiene acceso a este servicio')) {
                $message = 'El certificado no tiene acceso a este servicio. En HawCert compruebe: 1) que el certificado está asignado al servicio con slug «crm-apartamentos», 2) que la URL del servicio es exactamente: ' . $url;
            }
            Log::warning('HawCert loginWithCertificate: falló validateAccess', [
                'url_enviada' => $url,
                'message' => $resultAccess['message'] ?? null,
                'http_status' => $resultAccess['http_status'] ?? null,
            ]);
            throw ValidationException::withMessages([
                'certificate' => [$message],
            ]);
        }

        $accessKey = $resultAccess['access_key'] ?? null;
        if (!$accessKey) {
            Log::warning('HawCert loginWithCertificate: validateAccess OK pero sin access_key', [
                'resultAccess_keys' => array_keys($resultAccess),
            ]);
            throw ValidationException::withMessages([
                'certificate' => ['No se recibió clave de acceso desde HawCert.'],
            ]);
        }

        Log::info('HawCert loginWithCertificate: validateAccess OK, llamando validateKey', ['url' => $url]);

        $resultKey = $this->hawCert->validateKey($accessKey, $url);
        if (!$resultKey['success']) {
            Log::warning('HawCert loginWithCertificate: falló validateKey', [
                'url_enviada' => $url,
                'message' => $resultKey['message'] ?? null,
                'http_status' => $resultKey['http_status'] ?? null,
            ]);
            throw ValidationException::withMessages([
                'certificate' => [$resultKey['message'] ?? 'Error al validar el acceso.'],
            ]);
        }

        $user = $this->resolveOrCreateUser($resultKey);
        if (!$user) {
            throw ValidationException::withMessages([
                'certificate' => ['No se pudo asociar el certificado a un usuario del sistema.'],
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $this->redirectPath($user),
            ]);
        }

        return redirect()->intended($this->redirectPath($user));
    }

    /**
     * Validar certificado por certificate_key y hacer login.
     * POST /auth/hawcert/validate-certificate
     */
    public function validateCertificate(Request $request)
    {
        $request->validate([
            'certificate_key' => 'required|string',
            'service_slug' => 'nullable|string|max:100',
        ]);

        $result = $this->hawCert->validateCertificate(
            $request->input('certificate_key'),
            $request->input('service_slug')
        );

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'certificate_key' => [$result['message'] ?? 'Certificado no válido o sin acceso al servicio.'],
            ]);
        }

        $user = $this->resolveOrCreateUser($result);
        if (!$user) {
            throw ValidationException::withMessages([
                'certificate_key' => ['No se pudo asociar el certificado a un usuario del sistema.'],
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $this->redirectPath($user),
            ]);
        }

        return redirect()->intended($this->redirectPath($user));
    }

    /**
     * Validar access key (un solo uso) y hacer login.
     * POST /auth/hawcert/validate-key
     * La URL debe ser la de esta aplicación (mismo host que al generar la key en HawCert).
     */
    public function validateKey(Request $request)
    {
        $request->validate([
            'key' => 'required|string|size:51',
            'url' => 'nullable|string|url',
        ]);

        $key = $request->input('key');
        // URL del servicio (mismo host que al generar la key en HawCert); por defecto raíz de la app
        $url = $request->input('url') ?: $request->root();
        if (!preg_match('#^https?://#', $url)) {
            $url = $request->scheme() . '://' . $request->getHttpHost() . ($url ?: '/');
        }

        $result = $this->hawCert->validateKey($key, $url);

        if (!$result['success']) {
            throw ValidationException::withMessages([
                'key' => [$result['message'] ?? 'Clave de acceso inválida, expirada o ya utilizada.'],
            ]);
        }

        $user = $this->resolveOrCreateUser($result);
        if (!$user) {
            throw ValidationException::withMessages([
                'key' => ['No se pudo asociar el certificado a un usuario del sistema.'],
            ]);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'redirect' => $this->redirectPath($user),
            ]);
        }

        return redirect()->intended($this->redirectPath($user));
    }

    /**
     * Si el usuario llega por GET con ?access_key=... (redirect desde HawCert), validar y hacer login.
     * GET /auth/hawcert/callback
     */
    public function callback(Request $request)
    {
        $key = $request->query('access_key');
        if (!$key || strlen($key) !== 51) {
            return redirect()->route('login')->with('error', 'Clave de acceso inválida o ausente.');
        }

        // URL del servicio (mismo host que en validate-access), sin query
        $url = $request->root();
        $result = $this->hawCert->validateKey($key, $url);

        if (!$result['success']) {
            return redirect()->route('login')->with('error', $result['message'] ?? 'Clave de acceso inválida o ya utilizada.');
        }

        $user = $this->resolveOrCreateUser($result);
        if (!$user) {
            return redirect()->route('login')->with('error', 'No se pudo asociar el certificado a un usuario del sistema.');
        }

        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath($user));
    }

    /**
     * Obtener o crear usuario a partir de la respuesta HawCert.
     * Usa user.email (identificador efectivo) para buscar o crear.
     */
    protected function resolveOrCreateUser(array $result): ?User
    {
        $email = HawCertService::getEffectiveEmail($result);
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $user = User::where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $name = $result['user']['name'] ?? $result['certificate']['name'] ?? $email;
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => bcrypt(Str::random(32)),
            'role' => 'USER',
        ]);
    }

    protected function redirectPath(User $user): string
    {
        if ($user->role === 'ADMIN') {
            return '/dashboard';
        }
        if ($user->role === 'LIMPIEZA') {
            return '/limpiadora/dashboard';
        }
        if ($user->role === 'MANTENIMIENTO') {
            return '/mantenimiento/dashboard';
        }
        return '/gestion';
    }
}
