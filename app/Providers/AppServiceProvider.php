<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Forzar HTTPS en produccion (D21). En dev local seguimos en HTTP.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
