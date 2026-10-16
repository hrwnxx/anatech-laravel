<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // API responses return the data itself instead of wrapping it in {"data": ...}.
        JsonResource::withoutWrapping();

        // Password logins: 10 tries per minute for each email from each address,
        // so guessing is slow but one person can't lock out a whole shop's Wi-Fi.
        RateLimiter::for('login', fn (Request $request): Limit => Limit::perMinute(10)
            ->by(Str::lower($request->string('email')->toString()).'|'.$request->ip()));

        RateLimiter::for('google-login', fn (Request $request): Limit => Limit::perMinute(20)->by($request->ip()));
    }
}
