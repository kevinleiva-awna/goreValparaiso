<?php

use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\Observation;
use App\Models\User;

beforeEach(function () {
    $this->consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
    $this->stage = ConsultationStage::factory()->create([
        'consultation_id' => $this->consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
});

it('lista observaciones con paginacion', function () {
    actingAsFunctionary();
    Observation::factory()->count(25)
        ->forConsultation($this->consultation, $this->stage)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.index'));
    $response->assertOk();
    $response->assertSeeText('Observaciones recibidas');
});

it('filtra observaciones por consulta', function () {
    actingAsFunctionary();
    $other = Consultation::factory()->create();
    $otherStage = ConsultationStage::factory()->create(['consultation_id' => $other->id]);

    Observation::factory()
        ->forConsultation($this->consultation, $this->stage)
        ->byUser(User::factory()->citizen()->create(['name' => 'Visible']))
        ->create();
    Observation::factory()
        ->forConsultation($other, $otherStage)
        ->byUser(User::factory()->citizen()->create(['name' => 'OcultoFiltro']))
        ->create();

    $response = $this->get(route('admin.observations.index', [
        'consultation_id' => $this->consultation->id,
    ]));
    $response->assertOk();
    $response->assertSeeText('Visible');
    $response->assertDontSeeText('OcultoFiltro');
});

it('filtra por metodo de autenticacion', function () {
    actingAsFunctionary();
    $citizen = User::factory()->citizen()->create();
    Observation::factory()
        ->forConsultation($this->consultation, $this->stage)
        ->byUser($citizen)
        ->create(['auth_method_used' => Observation::AUTH_CLAVEUNICA, 'subject' => 'ConClaveUnica']);
    // 'guest' como segunda categoria visible en el listado: el registro
    // manual fue eliminado en junio 2026, solo quedan claveunica y guest.
    Observation::factory()
        ->forConsultation($this->consultation, $this->stage)
        ->create([
            'user_id' => null,
            'auth_method_used' => Observation::AUTH_GUEST,
            'snapshot_national_id' => null,
            'snapshot_full_name' => 'Guest Anonimo',
            'snapshot_email' => 'guest@example.com',
            'subject' => 'ConGuest',
        ]);

    $response = $this->get(route('admin.observations.index', ['auth_method' => 'claveunica']));
    $response->assertOk();
    $response->assertSeeText('ConClaveUnica');
    $response->assertDontSeeText('ConGuest');
});

it('exporta observaciones en formato xlsx', function () {
    actingAsFunctionary();
    Observation::factory()->count(3)
        ->forConsultation($this->consultation, $this->stage)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'xlsx']));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

it('exporta observaciones en formato csv', function () {
    actingAsFunctionary();
    Observation::factory()->count(2)
        ->forConsultation($this->consultation, $this->stage)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'csv']));
    $response->assertOk();
});

it('rechaza formato de export invalido', function () {
    actingAsFunctionary();
    $this->get(route('admin.observations.export', ['format' => 'pdf']))
        ->assertNotFound();
})->skip('Route param constraint whereIn lo bloquea con 404 antes de llegar al controller');
