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

        // Forzar HTTPS sólo si APP_URL ya lo declara. Asi preprod sin cert
        // (APP_URL=http://IP) sigue generando enlaces http y los assets cargan;
        // cuando se agregue dominio + ACM y APP_URL pase a https, el force se
        // activa solo. Antes era incondicional para production y rompia assets.
        if ($this->app->environment('production')
            && str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
