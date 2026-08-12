<?php

use App\Models\Consultation;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;

/**
 * Metricas del panel del backoffice (EETT, modulo de gestor de contenidos:
 * total de observaciones, observaciones por proceso y observaciones por dia).
 */

beforeEach(function () {
    $this->funcionario = User::factory()->functionary()->create();
    $this->consulta = Consultation::factory()->active()->create(['title' => 'PROT Valparaiso']);
});

it('muestra el total de observaciones recibidas', function () {
    Observation::factory()->count(3)->forConsultation($this->consulta)->create();

    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Observaciones recibidas')
        ->assertViewHas('total', 3);
});

it('excluye las observaciones archivadas del total', function () {
    $observaciones = Observation::factory()->count(3)->forConsultation($this->consulta)->create();
    $observaciones->first()->delete();

    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertViewHas('total', 2);
});

it('cuenta las observaciones sin respuesta institucional', function () {
    $observaciones = Observation::factory()->count(3)->forConsultation($this->consulta)->create();
    InstitutionalResponse::create([
        'observation_id' => $observaciones->first()->id,
        'content' => 'Respondida.',
        'responded_by' => $this->funcionario->id,
        'responded_at' => now(),
        'status' => InstitutionalResponse::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertViewHas('sinRespuesta', 2);
});

it('desglosa las observaciones por proceso, de mayor a menor', function () {
    $otra = Consultation::factory()->active()->create(['title' => 'ZUBC Region']);
    Observation::factory()->count(2)->forConsultation($this->consulta)->create();
    Observation::factory()->count(5)->forConsultation($otra)->create();

    $response = $this->actingAs($this->funcionario)->get(route('dashboard'));

    $porProceso = $response->viewData('porProceso');
    expect($porProceso->pluck('title')->all())->toBe(['ZUBC Region', 'PROT Valparaiso']);
    expect($porProceso->pluck('observations_count')->all())->toBe([5, 2]);
});

it('incluye en el desglose los procesos archivados que tienen observaciones', function () {
    // Sus observaciones siguen sumando al total; si el desglose las omitiera,
    // la suma por proceso no cuadraria con el total.
    Observation::factory()->count(4)->forConsultation($this->consulta)->create();
    $this->consulta->delete();

    $response = $this->actingAs($this->funcionario)->get(route('dashboard'));

    expect($response->viewData('total'))->toBe(4);
    expect($response->viewData('porProceso')->sum('observations_count'))->toBe(4);
});

it('omite del desglose los procesos sin observaciones', function () {
    Consultation::factory()->active()->create(['title' => 'Proceso sin participacion']);

    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertDontSee('Proceso sin participacion');
});

it('entrega la serie diaria con los dias vacios en cero', function () {
    Observation::factory()->forConsultation($this->consulta)
        ->create(['submitted_at' => now()->subDays(2)]);
    Observation::factory()->count(2)->forConsultation($this->consulta)
        ->create(['submitted_at' => now()]);

    $porDia = $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->viewData('porDia');

    expect($porDia)->toHaveCount(30);
    expect($porDia->sum('total'))->toBe(3);
    expect($porDia->last()['total'])->toBe(2);
    // El dia intermedio existe en la serie aunque no tenga observaciones.
    expect($porDia[28]['total'])->toBe(0);
});

it('deja fuera de la serie diaria lo anterior a la ventana', function () {
    Observation::factory()->forConsultation($this->consulta)
        ->create(['submitted_at' => now()->subDays(45)]);

    $response = $this->actingAs($this->funcionario)->get(route('dashboard'));

    expect($response->viewData('total'))->toBe(1);
    expect($response->viewData('porDia')->sum('total'))->toBe(0);
});

it('cuenta solo los procesos abiertos hoy', function () {
    // Activa pero con la ventana ya vencida: no admite participacion.
    Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'starts_at' => now()->subDays(30),
        'ends_at' => now()->subDay(),
    ]);
    // Activa y por comenzar.
    Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'starts_at' => now()->addDays(5),
        'ends_at' => now()->addDays(30),
    ]);

    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertViewHas('procesosActivos', 1);
});

it('el panel sigue restringido al personal del backoffice', function () {
    $this->actingAs(User::factory()->citizen()->create())
        ->get(route('dashboard'))
        ->assertForbidden();
});

it('el panel responde sin datos', function () {
    $this->actingAs($this->funcionario)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Todavia no se reciben observaciones');
});
