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

it('navbar: sin login se ofrece ClaveUnica con el boton oficial', function () {
    $response = $this->get('/');

    $response->assertOk();
    // El marcado del manual de marca: clases oficiales, isotipo y aria-label.
    // Si alguien "arregla" el boton para que calce con la paleta del sitio,
    // esta prueba cae: cambiarlo invalida la certificacion.
    $response->assertSee('btn-cu btn-m btn-color-estandar', escape: false);
    $response->assertSee('class="cl-claveunica"', escape: false);
    $response->assertSee('aria-label="Continuar con ClaveÚnica"', escape: false);
    $response->assertDontSee('Ir al backoffice');
});
