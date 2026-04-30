<?php

namespace App\Providers;

use App\Models\Reserva;
use App\Observers\ReservaObserver;
use App\Services\ChatGptService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // $this->app->singleton(ChatGptService::class, function ($app) {
        //     return new ChatGptService();
        // });
    
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('pagination::bootstrap-5');
        Paginator::defaultSimpleView('vendor.pagination.bootstrap-5');

        // [2026-04-28] Observer de reservas: maneja cambios en fecha_salida
        // (reencolar Job de borrado) y cancelaciones (revocar PIN en
        // cerradura). Necesario para mantener coherencia entre BD y la
        // cerradura fisica.
        Reserva::observe(ReservaObserver::class);
    }
}
