<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MobileRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // No aplicar en rutas de tareas específicas
        if ($request->is('gestion/tareas/*')) {
            return $next($request);
        }
        
        // Solo aplicar después del login exitoso
        if (Auth::check() && $request->session()->has('auth.password_confirmed_at')) {
            $user = Auth::user();
            
            // Detectar si es dispositivo móvil
            $isMobile = $this->isMobileDevice($request);
            
            // Si es móvil y usuario normal, redirigir a gestión
            if ($isMobile && $user->role === 'USER') {
                $request->session()->forget('auth.password_confirmed_at');
                return redirect('/gestion');
            }
            
            // Si es móvil y admin, redirigir a dashboard
            if ($isMobile && $user->role === 'ADMIN') {
                $request->session()->forget('auth.password_confirmed_at');
                return redirect('/admin');
            }
            
            // Si es móvil y limpiadora, redirigir a dashboard de limpiadora
            if ($isMobile && $user->role === 'LIMPIEZA') {
                $request->session()->forget('auth.password_confirmed_at');
                return redirect('/gestion');
            }
        }

        return $next($request);
    }

    /**
     * Detect if the request is from a mobile device
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function isMobileDevice(Request $request)
    {
        $userAgent = $request->header('User-Agent');
        
        if (empty($userAgent)) {
            return false;
        }

        // Patrones comunes de dispositivos móviles
        $mobilePatterns = [
            'Mobile',
            'Android',
            'iPhone',
            'iPad',
            'Windows Phone',
            'BlackBerry',
            'Opera Mini',
            'IEMobile',
            'Mobile Safari'
        ];

        foreach ($mobilePatterns as $pattern) {
            if (stripos($userAgent, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }
}
