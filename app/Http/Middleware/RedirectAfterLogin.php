<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RedirectAfterLogin
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
        // Solo aplicar si el usuario está autenticado y viene del login
        if (Auth::check() && $request->session()->has('auth.password_confirmed_at')) {
            $user = Auth::user();
            
            // Limpiar la marca de login para evitar redirecciones infinitas
            $request->session()->forget('auth.password_confirmed_at');
            
            // Redirigir según el rol
            if ($user->role === 'ADMIN') {
                return redirect('/admin');
            } elseif ($user->role === 'USER') {
                return redirect('/home');
            } elseif ($user->role === 'LIMPIEZA') {
                return redirect('/limpiadora/dashboard');
            } elseif ($user->role === 'MANTENIMIENTO') {
                return redirect('/mantenimiento/dashboard');
            }
        }

        return $next($request);
    }
}
