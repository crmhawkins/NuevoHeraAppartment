<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    /**
     * Handle an incoming request.
     * Verifies that the request contains a valid API key via header or query parameter.
     */
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-API-Key') ?? $request->query('api_key');

        if (!$key || $key !== config('services.whatsapp_tools.api_key')) {
            return response()->json(['error' => 'API key required'], 401);
        }

        return $next($request);
    }
}
