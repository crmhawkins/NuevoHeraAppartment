<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class LogAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Log authentication events
        $this->logAuthenticationEvent($request, $response);
        
        return $response;
    }

    /**
     * Log authentication events
     */
    private function logAuthenticationEvent(Request $request, Response $response): void
    {
        $user = Auth::user();
        $path = $request->path();
        
        // Log login attempts
        if ($path === 'login' && $request->isMethod('POST')) {
            $this->logLoginAttempt($request, $response);
        }
        
        // Log logout
        if ($path === 'logout' && $request->isMethod('POST')) {
            $this->logLogout($user);
        }
        
        // Log password reset requests
        if (str_contains($path, 'password/reset') && $request->isMethod('POST')) {
            $this->logPasswordResetRequest($request);
        }
        
        // Log password reset confirmations
        if (str_contains($path, 'password/reset') && $request->isMethod('PUT')) {
            $this->logPasswordResetConfirmation($request, $response);
        }
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt(Request $request, Response $response): void
    {
        $email = $request->input('email');
        $isSuccessful = $response->getStatusCode() === 302 && $response->headers->get('Location') !== route('login');
        
        $logData = [
            'type' => 'AUTHENTICATION',
            'event' => $isSuccessful ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED',
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'success' => $isSuccessful
        ];

        $level = $isSuccessful ? 'info' : 'warning';
        Log::channel('daily')->$level('Authentication Event', $logData);
    }

    /**
     * Log logout
     */
    private function logLogout($user): void
    {
        if ($user) {
            $logData = [
                'type' => 'AUTHENTICATION',
                'event' => 'LOGOUT',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'user_role' => $user->role,
                'timestamp' => now()->toISOString()
            ];

            Log::channel('daily')->info('Authentication Event', $logData);
        }
    }

    /**
     * Log password reset request
     */
    private function logPasswordResetRequest(Request $request): void
    {
        $email = $request->input('email');
        
        $logData = [
            'type' => 'AUTHENTICATION',
            'event' => 'PASSWORD_RESET_REQUEST',
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString()
        ];

        Log::channel('daily')->info('Authentication Event', $logData);
    }

    /**
     * Log password reset confirmation
     */
    private function logPasswordResetConfirmation(Request $request, Response $response): void
    {
        $email = $request->input('email');
        $isSuccessful = $response->getStatusCode() === 302;
        
        $logData = [
            'type' => 'AUTHENTICATION',
            'event' => $isSuccessful ? 'PASSWORD_RESET_SUCCESS' : 'PASSWORD_RESET_FAILED',
            'email' => $email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'success' => $isSuccessful
        ];

        $level = $isSuccessful ? 'info' : 'warning';
        Log::channel('daily')->$level('Authentication Event', $logData);
    }
}
