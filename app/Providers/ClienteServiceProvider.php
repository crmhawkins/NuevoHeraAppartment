<?php

namespace App\Providers;

use App\Services\ClienteService;
use Illuminate\Support\ServiceProvider;

class ClienteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ClienteService::class, function ($app) {
            return new ClienteService();
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
