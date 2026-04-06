<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class BlockSiteAndRedirect
{
    /**
     * Handle an incoming request.
     * 
     * 1. Si se accede desde CRM (crm.apartamentosalgeciras.com) y la ruta es /web/*:
     *    - Bloquea y redirige al dashboard del CRM (/admin)
     * 
     * 2. Si se accede desde el dominio principal (apartamentosalgeciras.com):
     *    - Si el bloqueo está activado, redirige a la URL configurada en .env
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $path = $request->path();
        $isMainDomain = $host === 'apartamentosalgeciras.com' || $host === 'www.apartamentosalgeciras.com';
        $isCrmDomain = str_starts_with($host, 'crm.');
        
        // Si es el dominio CRM y está intentando acceder a rutas /web/*, bloquear y redirigir al CRM
        if ($isCrmDomain && (str_starts_with($path, 'web') || $path === 'web')) {
            Log::info('BlockSiteAndRedirect: Bloqueando acceso a web pública desde CRM', [
                'host' => $host,
                'path' => $path,
                'url' => $request->fullUrl(),
            ]);
            
            // Redirigir al dashboard del CRM
            return redirect('/admin', 302);
        }
        
        // Si es el dominio CRM y NO es una ruta /web/*, permitir el acceso
        if ($isCrmDomain) {
            return $next($request);
        }
        
        // Solo procesar bloqueo si es el dominio principal
        if (!$isMainDomain) {
            return $next($request);
        }
        
        // Obtener la URL de redirección del .env
        $redirectUrl = env('SITE_BLOCK_REDIRECT_URL', null);
        
        // Si está activado el bloqueo de la web principal
        $blockEnabled = filter_var(env('SITE_BLOCK_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
        
        // Si está activado el bloqueo solo de la web principal (alias)
        $blockMainOnly = filter_var(env('SITE_BLOCK_MAIN_ONLY', false), FILTER_VALIDATE_BOOLEAN);
        
        // Bloquear solo si está activado el bloqueo (cualquiera de las dos opciones)
        $shouldBlock = $blockEnabled || $blockMainOnly;
        
        if ($shouldBlock) {
            if (empty($redirectUrl)) {
                Log::warning('BlockSiteAndRedirect: URL de redirección no configurada en .env', [
                    'host' => $host,
                    'block_enabled' => $blockEnabled,
                    'block_main_only' => $blockMainOnly,
                    'is_main_domain' => $isMainDomain,
                    'is_crm_domain' => $isCrmDomain,
                ]);
                
                // Si no hay URL configurada, devolver un error 503
                return response()->view('errors.503', [], 503);
            }
            
            Log::info('BlockSiteAndRedirect: Redirigiendo acceso bloqueado', [
                'host' => $host,
                'url' => $request->fullUrl(),
                'redirect_to' => $redirectUrl,
                'block_enabled' => $blockEnabled,
                'block_main_only' => $blockMainOnly,
                'is_main_domain' => $isMainDomain,
                'is_crm_domain' => $isCrmDomain,
            ]);
            
            return redirect($redirectUrl, 302);
        }
        
        return $next($request);
    }
}
