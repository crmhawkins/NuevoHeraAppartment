<?php

namespace App\Providers;

use App\Models\Anio;
use ChatGptService;
use Illuminate\Support\ServiceProvider;

class AnioServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('anio', function ($app) {
            // Aquí obtenemos el último año configurado
            return Anio::latest()->first()->anio ?? date('Y');
        });
     
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
