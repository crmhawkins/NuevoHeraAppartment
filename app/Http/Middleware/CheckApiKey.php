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

        if (!$key || $key !== env('WHATSAPP_TOOLS_API_KEY')) {
            return response()->json(['error' => 'API key required'], 401);
        }

        return $next($request);
    }
}
