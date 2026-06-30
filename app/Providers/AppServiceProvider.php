<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Support\Context\CurrentContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // super_admin bypasses all authorization (reproducible without shield:generate).
        Gate::before(fn ($user, $ability) => $user->hasRole('super_admin') ? true : null);
    }
}
