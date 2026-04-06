<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class CookieServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Asegurar que la configuración de cookies esté disponible
        config([
            'cookies.path' => '/',
            'cookies.domain' => null,
            'cookies.secure' => false,
            'cookies.http_only' => true,
            'cookies.same_site' => 'lax',
        ]);
    }
}
