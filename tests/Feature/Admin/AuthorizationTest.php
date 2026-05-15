<?php

use App\Models\User;

/**
 * Autorizacion por rol en /admin/* (D21 + middleware role).
 *
 * Cubre los tres roles relevantes: ciudadano, funcionario, super-admin,
 * mas usuario inactivo.
 */

it('rechaza acceso de guest al backoffice', function () {
    $this->get(route('admin.consultations.index'))
        ->assertRedirect(route('login'));
});

it('rechaza acceso de ciudadano al backoffice', function () {
    actingAsCitizen();
    $this->get(route('admin.consultations.index'))->assertForbidden();
    $this->get(route('admin.observations.index'))->assertForbidden();
});

it('rechaza acceso de funcionario inactivo', function () {
    $user = User::factory()->functionary()->inactive()->create();
    $this->actingAs($user);

    $this->get(route('admin.consultations.index'))->assertForbidden();
});

it('permite acceso de funcionario activo a consultas y observaciones', function () {
    actingAsFunctionary();

    $this->get(route('admin.consultations.index'))->assertOk();
    $this->get(route('admin.observations.index'))->assertOk();
});

it('restringe gestion de usuarios solo a super-admin', function () {
    actingAsFunctionary();
    $this->get(route('admin.users.index'))->assertForbidden();

    actingAsSuperAdmin();
    $this->get(route('admin.users.index'))->assertOk();
});

it('restringe bitacora solo a super-admin', function () {
    actingAsFunctionary();
    $this->get(route('admin.activity-log.index'))->assertForbidden();

    actingAsSuperAdmin();
    $this->get(route('admin.activity-log.index'))->assertOk();
});
