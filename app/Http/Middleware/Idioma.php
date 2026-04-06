<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;


class Idioma
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;
        
        // Prioridad 1: Verificar si hay un idioma en la sesión (clave 'locale')
        if (Session::has('locale')) {
            $locale = Session::get('locale');
            Log::debug('Middleware Idioma: locale obtenido desde sesión', ['locale' => $locale]);
        } 
        // Prioridad 2: Verificar clave legacy 'idioma'
        elseif (Session::has('idioma')) {
            $locale = Session::get('idioma');
            // Migrar a 'locale' para consistencia
            Session::put('locale', $locale);
            Session::save();
            Log::debug('Middleware Idioma: locale migrado desde idioma', ['idioma' => $locale, 'locale' => $locale]);
        }
        // Prioridad 3: Verificar si el usuario autenticado tiene un idioma preferido
        elseif (auth('cliente')->check()) {
            $cliente = auth('cliente')->user();
            if ($cliente && $cliente->idioma) {
                $locale = $cliente->idioma;
                // Guardar en sesión para futuras peticiones
                Session::put('locale', $locale);
                Session::save();
                Log::debug('Middleware Idioma: locale obtenido desde cliente autenticado', ['locale' => $locale, 'cliente_id' => $cliente->id]);
            }
        }
        
        // Si no se encontró ningún idioma, usar español por defecto
        if (!$locale) {
            $locale = 'es';
            Session::put('locale', $locale);
            Session::save();
            Log::debug('Middleware Idioma: usando locale por defecto', ['locale' => $locale]);
        }
        
        // Validar que el locale sea válido
        $idiomasValidos = ['es', 'en', 'fr', 'de', 'it', 'pt'];
        if (!in_array($locale, $idiomasValidos)) {
            $locale = 'es';
            Session::put('locale', $locale);
            Session::save();
            Log::warning('Middleware Idioma: locale no válido, usando default', ['locale_invalido' => $locale]);
        }
        
        // Establecer el locale en la aplicación
        App::setLocale($locale);
        
        // Log para depuración
        Log::info('Middleware Idioma ejecutado', [
            'locale_establecido' => $locale,
            'locale_aplicacion' => App::getLocale(),
            'session_locale' => Session::get('locale'),
            'session_id' => Session::getId(),
            'url' => $request->fullUrl()
        ]);
        
        return $next($request);
    }
}
