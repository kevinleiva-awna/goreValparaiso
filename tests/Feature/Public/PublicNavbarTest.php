<?php

use App\Models\User;

/**
 * Menu de cuenta del navbar publico (acta jun 2026): tras quitar el boton
 * prominente "Ir al backoffice", el acceso a la cuenta vive en un dropdown
 * de avatar. Verifica que las rutas (perfil/panel/logout) resuelvan y que
 * cada rol vea lo correcto.
 */

it('navbar: funcionario ve perfil, panel y cerrar sesion', function () {
    $this->actingAs(User::factory()->functionary()->create());

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Mi perfil');
    $response->assertSee('Ir al panel');
    $response->assertSee('Cerrar sesion');
    // Las rutas de funcionario resuelven (si no, el render lanzaria excepcion).
    $response->assertSee(route('profile.edit'), false);
    $response->assertSee(route('dashboard'), false);
});

it('navbar: ciudadano ve cerrar sesion pero no el panel', function () {
    $this->actingAs(User::factory()->citizen()->create());

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Cerrar sesion');
    $response->assertDontSee('Ir al panel');
});

it('navbar: sin login se ofrece ClaveUnica', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertSee('Ingresar con ClaveUnica');
    $response->assertDontSee('Ir al backoffice');
});
