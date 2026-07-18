<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->registerApiRateLimiters();
    }

    /** Mobile /api/v1 throttles (docs/ARCHITECTURE_X2_PLATFORM_V1.md §4.5). */
    private function registerApiRateLimiters(): void
    {
        // OTP: per destination — abuse-resistant.
        RateLimiter::for('otp', fn (Request $r) => [
            Limit::perMinutes(10, 5)->by((string) ($r->input('destination') ?: $r->ip())),
        ]);

        // Login: per ip+device.
        RateLimiter::for('auth-login', fn (Request $r) => [
            Limit::perMinute(10)->by($r->ip().'|'.$r->header('X-Device-Id', 'nodev')),
        ]);

        // Public reads (unauthenticated).
        RateLimiter::for('public-read', fn (Request $r) => [Limit::perMinute(120)->by($r->ip())]);

        // Authenticated reads/writes — keyed by user when present.
        RateLimiter::for('api', fn (Request $r) => [
            Limit::perMinute(300)->by($r->user()?->id ? 'u:'.$r->user()->id : 'ip:'.$r->ip()),
        ]);
    }
}
