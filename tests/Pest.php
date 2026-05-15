<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Todos los tests Feature corren contra una BD SQLite en memoria con
| RefreshDatabase. Vite se deshabilita globalmente para no requerir build
| del frontend al testear vistas Blade.
|
*/

pest()->extend(TestCase::class)
    ->beforeEach(function () {
        $this->withoutVite();
    })
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function actingAsCitizen(): \App\Models\User
{
    $user = \App\Models\User::factory()->citizen()->create();
    test()->actingAs($user);
    return $user;
}

function actingAsFunctionary(): \App\Models\User
{
    $user = \App\Models\User::factory()->functionary()->create();
    test()->actingAs($user);
    return $user;
}

function actingAsSuperAdmin(): \App\Models\User
{
    $user = \App\Models\User::factory()->superAdmin()->create();
    test()->actingAs($user);
    return $user;
}
