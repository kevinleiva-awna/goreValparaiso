<?php

use App\Http\Controllers\Admin\ObservationController as AdminObservationController;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Csp\AddCspHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Confiar en headers X-Forwarded-* de proxies (Cloudflare, AWS ELB,
        // Nginx reverse proxy, tuneles tipo cloudflared/ngrok). Sin esto,
        // Laravel detras de proxy generaria URLs http en vez de https y
        // veria la IP del proxy en lugar de la IP real del cliente.
        $middleware->trustProxies(at: '*');

        // El JS del listado de observaciones lee esta cookie para saber que la
        // descarga ya salio del servidor y apagar el spinner del boton
        // Exportar. Encriptada no le sirve: veria un blob que no puede
        // comparar con el token que mando. No lleva nada sensible, es un
        // identificador aleatorio que genero el propio navegador.
        $middleware->encryptCookies(except: [
            AdminObservationController::DOWNLOAD_COOKIE,
        ]);

        // Cabeceras de seguridad + CSP estricta aplicadas a todas las rutas web (D21).
        $middleware->web(append: [
            SecurityHeaders::class,
            AddCspHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
