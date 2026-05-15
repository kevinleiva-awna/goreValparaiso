<?php

use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;

it('lista solo consultas en estado visible en /consultas', function () {
    Consultation::factory()->create(['status' => Consultation::STATUS_DRAFT, 'title' => 'Borrador oculto']);
    Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE, 'title' => 'Activa visible']);
    Consultation::factory()->create(['status' => Consultation::STATUS_ARCHIVED, 'title' => 'Archivada oculta']);

    $response = $this->get('/consultas');

    $response->assertOk();
    $response->assertSeeText('Activa visible');
    $response->assertDontSeeText('Borrador oculto');
    $response->assertDontSeeText('Archivada oculta');
});

it('muestra la ficha publica de una consulta por slug', function () {
    $consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'mi-proceso',
        'title' => 'PROT de Valparaiso',
    ]);

    $this->get("/consultas/{$consultation->slug}")
        ->assertOk()
        ->assertSeeText('PROT de Valparaiso');
});

it('expone respuestas publicadas en la ficha publica sin filtrar RUT ni email', function () {
    $functionary = User::factory()->functionary()->create();
    $consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
    $stage = ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
    $citizen = User::factory()->citizen()->create([
        'national_id' => '15555555-K',
        'email' => 'privado@ejemplo.cl',
    ]);
    $obs = Observation::factory()
        ->forConsultation($consultation, $stage)
        ->byUser($citizen)
        ->create(['subject' => 'Asunto X']);

    InstitutionalResponse::factory()->published()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
        'content' => 'Texto unico de respuesta publicada.',
    ]);

    $response = $this->get("/consultas/{$consultation->slug}");
    $response->assertOk();
    $response->assertSeeText('Texto unico de respuesta publicada');
    $response->assertSeeText($citizen->name);
    $response->assertDontSeeText('15555555-K');         // RUT NO debe estar
    $response->assertDontSeeText('privado@ejemplo.cl'); // email NO debe estar
});

it('NO expone respuestas en borrador en la ficha publica', function () {
    $functionary = User::factory()->functionary()->create();
    $consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
    $stage = ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
    $citizen = User::factory()->citizen()->create();
    $obs = Observation::factory()
        ->forConsultation($consultation, $stage)
        ->byUser($citizen)
        ->create();

    InstitutionalResponse::factory()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
        'content' => 'Borrador que no debe filtrarse al publico jamas.',
    ]);

    $response = $this->get("/consultas/{$consultation->slug}");
    $response->assertOk();
    $response->assertDontSeeText('Borrador que no debe filtrarse');
});
