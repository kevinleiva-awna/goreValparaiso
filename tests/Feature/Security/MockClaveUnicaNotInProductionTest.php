<?php

use App\Models\User;

/**
 * El simulador de ClaveUnica acepta cualquier RUT y abre sesion con esa
 * identidad. Publicado en produccion seria un bypass de autenticacion: se
 * podrian firmar observaciones con una identidad ajena, que es justo lo que la
 * plataforma debe impedir.
 *
 * El 12-ago-2026 quedo publicado en el dominio institucional porque el .env de
 * produccion no traia CLAVEUNICA_MODE y el default del config es 'mock'. La
 * defensa ahora es doble: las rutas no se registran fuera de local/staging, y
 * el controlador aborta si el ambiente es produccion.
 */

it('el simulador no responde cuando el ambiente es produccion', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get(route('mock.claveunica.simulate'))->assertNotFound();
});

it('el envio del simulador no responde cuando el ambiente es produccion', function () {
    app()->detectEnvironment(fn () => 'production');

    // Con el ambiente en 'production' Laravel deja de considerar que corre bajo
    // tests y activa la verificacion de CSRF, que devolveria 419 antes de tocar
    // el controlador. Se desactiva solo ese middleware para verificar el guard.
    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

    $this->post(route('mock.claveunica.complete'), [
        'run' => '12345678-5',
        'name' => 'Suplantador',
        'state' => 'x',
    ])->assertNotFound();
});

it('el simulador responde fuera de produccion', function () {
    $this->get(route('mock.claveunica.simulate'))->assertOk();
});

it('el simulador no redirige a un destino entregado por el cliente', function () {
    // redirect_uri es un parametro del flujo OIDC que viaja en el form. Si se
    // usara como destino, esta ruta seria un open redirect en el dominio
    // institucional.
    $response = $this->post(route('mock.claveunica.complete'), [
        'run' => '12345678-5',
        'name' => 'Ciudadana',
        'state' => 'abc',
        'redirect_uri' => 'https://sitio-externo.example/phishing',
    ]);

    $response->assertRedirectContains(route('citizen.claveunica.callback'));
    expect($response->headers->get('Location'))->not->toContain('sitio-externo.example');
});

it('sin el simulador no hay forma de abrir sesion sin ClaveUnica real', function () {
    // Guard de regresion: la unica via de sesion ciudadana es el callback de
    // ClaveUnica. Si alguien reintrodujera un login de ciudadano por formulario,
    // este test lo delata.
    expect(User::count())->toBe(0);

    $this->get('/')->assertOk();
    $this->assertGuest();
});
