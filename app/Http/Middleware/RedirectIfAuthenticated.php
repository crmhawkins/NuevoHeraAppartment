<?php

namespace App\Http\Middleware;

use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;
        foreach ($guards as $guard) {
            if (Auth::check()) {
                $user = Auth::user();
                
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
        }

        return $next($request);
    }
}
