<?php

namespace App\Providers;

use App\Support\DatabaseDumper;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DatabaseDumper::class, fn () => DatabaseDumper::forDefault());
    }

    public function boot(): void
    {
        if ($proxies = config('security.trusted_proxies')) {
            TrustProxies::at($proxies === '*' ? '*' : array_map('trim', explode(',', $proxies)));
        }

        if (config('security.force_https')) {
            URL::forceScheme('https');
        }
    }
}
