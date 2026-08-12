<?php

use App\Models\User;

/**
 * Logout federado con ClaveUnica.
 *
 * Cerrar la sesion local no basta: si no se pasa por el endpoint de logout del
 * IdP, la sesion en accounts.claveunica.gob.cl sigue viva y el siguiente
 * "Ingresar" entra sin pedir credenciales. La ruta de aterrizaje
 * (citizen.claveunica.logout) es la que se declara como "Logout URI" ante
 * ClaveUnica — ver docs/claveunica/solicitud-credenciales.md.
 */

it('logout en modo live rebota al endpoint de logout de ClaveUnica', function () {
    config()->set('claveunica.enabled', true);
    config()->set('claveunica.mode', 'live');

    $this->actingAs(User::factory()->citizen()->create())
        ->withSession(['auth_method' => 'claveunica']);

    $response = $this->post(route('citizen.logout'));

    $response->assertRedirect(
        config('claveunica.logout_url') . '?redirect=' . urlencode(route('citizen.claveunica.logout'))
    );
    $this->assertGuest();
});

it('logout en modo mock se queda en la app', function () {
    config()->set('claveunica.enabled', true);
    config()->set('claveunica.mode', 'mock');

    $this->actingAs(User::factory()->citizen()->create())
        ->withSession(['auth_method' => 'claveunica']);

    $response = $this->post(route('citizen.logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

it('logout de un funcionario no pasa por ClaveUnica aunque el modo sea live', function () {
    config()->set('claveunica.enabled', true);
    config()->set('claveunica.mode', 'live');

    // El backoffice entra con email/password, no con ClaveUnica: mandarlo al
    // logout del IdP cerraria una sesion que nunca abrio.
    $this->actingAs(User::factory()->functionary()->create());

    $response = $this->post(route('citizen.logout'));

    $response->assertRedirect(route('home'));
    $this->assertGuest();
});

it('la logout URI aterriza en el home sin requerir sesion', function () {
    $response = $this->get(route('citizen.claveunica.logout'));

    $response->assertRedirect(route('home'));
});

it('la logout URI no cierra sesion por GET', function () {
    // Un GET que desloguea es accionable desde un sitio de terceros (basta un
    // <img src>). El cierre real vive en el POST protegido por CSRF.
    $user = User::factory()->citizen()->create();
    $this->actingAs($user);

    $this->get(route('citizen.claveunica.logout'))->assertRedirect(route('home'));

    $this->assertAuthenticatedAs($user);
});
