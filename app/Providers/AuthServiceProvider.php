<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Log Viewer (opcodesio/log-viewer): solo usuarios con rol ADMIN
        Gate::define('viewLogViewer', function ($user) {
            return $user && $user->hasRole('ADMIN');
        });
    }
}
