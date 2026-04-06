<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        
        // Log the incoming request
        $this->logRequest($request);
        
        $response = $next($request);
        
        // Log the response
        $this->logResponse($request, $response, $startTime);
        
        return $response;
    }

    /**
     * Log incoming request
     */
    private function logRequest(Request $request): void
    {
        $user = Auth::user();
        
        $logData = [
            'type' => 'REQUEST',
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Guest',
            'user_email' => $user ? $user->email : null,
            'user_role' => $user ? $user->role : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'referer' => $request->header('referer'),
            'content_type' => $request->header('content-type'),
            'content_length' => $request->header('content-length'),
            'timestamp' => now()->toISOString(),
            'request_data' => $this->sanitizeRequestData($request)
        ];

        Log::channel('daily')->info('HTTP Request', $logData);
    }

    /**
     * Log response
     */
    private function logResponse(Request $request, Response $response, float $startTime): void
    {
        $user = Auth::user();
        $executionTime = round((microtime(true) - $startTime) * 1000, 2); // in milliseconds
        
        $logData = [
            'type' => 'RESPONSE',
            'user_id' => $user ? $user->id : null,
            'user_name' => $user ? $user->name : 'Guest',
            'user_email' => $user ? $user->email : null,
            'user_role' => $user ? $user->role : null,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'response_size' => strlen($response->getContent()),
            'execution_time_ms' => $executionTime,
            'ip_address' => $request->ip(),
            'timestamp' => now()->toISOString()
        ];

        // Use different log levels based on status code
        $level = $this->getLogLevel($response->getStatusCode());
        Log::channel('daily')->$level('HTTP Response', $logData);
    }

    /**
     * Get appropriate log level based on status code
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } else {
            return 'info';
        }
    }

    /**
     * Sanitize sensitive request data
     */
    private function sanitizeRequestData(Request $request): array
    {
        $sensitiveFields = [
            'password', 'password_confirmation', 'token', 'api_key', 'secret',
            'dni', 'telefono', 'email', 'credit_card', 'bank_account',
            'ssn', 'social_security_number', 'pin', 'otp', '_token'
        ];

        $data = $request->all();
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $sensitiveFields);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Recursively sanitize array data
     */
    private function sanitizeArray(array $data, array $sensitiveFields): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveFields)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $sensitiveFields);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
