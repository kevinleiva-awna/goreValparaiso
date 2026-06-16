<?php

use App\Models\Consultation;
use App\Models\ConsultationStage;

/**
 * Cobertura del estado "efectivo" de una consulta en la ficha publica.
 *
 * Bug reportado por GORE (junio 2026): una consulta cuya fecha de termino ya
 * paso seguia mostrandose "Consulta activa / En curso" con el formulario de
 * observaciones abierto, porque el status almacenado seguia 'active' (cierre
 * manual pendiente) y la determinacion no miraba la fecha. El guard de fecha
 * cierra esa brecha sin depender de un cron de cierre.
 */

it('muestra una consulta vencida como cerrada aunque el status siga active', function () {
    // status=active pero la ventana ya expiro (situacion de pri-sur-tdas).
    $consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'vencida-pero-activa',
        'title' => 'Proceso con fecha vencida',
        'starts_at' => now()->subDays(18),
        'ends_at' => now()->subDays(11),
        'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
    ]);
    ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'name' => 'Recepcion de observaciones',
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
        'starts_at' => now()->subDays(18),
        'ends_at' => now()->subDays(11),
    ]);

    $response = $this->get("/consultas/{$consultation->slug}");

    $response->assertOk();
    // Badge del proceso: cerrada, no activa.
    $response->assertSeeText('Consulta cerrada');
    $response->assertDontSeeText('Consulta activa');
    // Badge de la etapa: finalizada, no "En curso".
    $response->assertSeeText('Finalizada');
    $response->assertDontSeeText('En curso');
    // Gate cerrado: NO se ofrece el formulario de participacion.
    $response->assertSeeText('Proceso cerrado');
    $response->assertDontSeeText('Tipo de participante');
    // Y el modelo coincide.
    expect($consultation->isOpenForObservations())->toBeFalse()
        ->and($consultation->effectiveStatus())->toBe(Consultation::STATUS_CLOSED);
});

it('en el listado /consultas etiqueta una consulta vencida como Cerrada', function () {
    Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'title' => 'Vencida en el listado',
        'starts_at' => now()->subDays(18),
        'ends_at' => now()->subDays(11),
    ]);

    $response = $this->get('/consultas');

    $response->assertOk();
    $response->assertSeeText('Vencida en el listado');
    // El badge de estado activo (gore-badge-success) solo se renderiza para
    // consultas activas; con la unica consulta vencida, no debe aparecer.
    $response->assertDontSee('gore-badge-success');
});

it('el home no destaca ni lista una consulta activa vencida', function () {
    // Bug #1 en la portada: una activa con fecha vencida no debe figurar como
    // destacada ("en curso / 0 dias") ni en "Consultas vigentes".
    Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'title' => 'Proceso Vencido Portada',
        'starts_at' => now()->subDays(20),
        'ends_at' => now()->subDays(6),
    ]);
    Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'title' => 'Proceso Vigente Portada',
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(15),
    ]);

    $response = $this->get('/');

    $response->assertOk();
    $response->assertSeeText('Proceso Vigente Portada');
    $response->assertDontSeeText('Proceso Vencido Portada');
});

it('mantiene abierta una consulta activa dentro de su ventana', function () {
    $consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'activa-vigente',
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(10),
        'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
    ]);
    ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
        'starts_at' => now()->subDays(3),
        'ends_at' => now()->addDays(10),
    ]);

    $response = $this->get("/consultas/{$consultation->slug}");

    $response->assertOk();
    $response->assertSeeText('Consulta activa');
    $response->assertSeeText('Tipo de participante');
    expect($consultation->isOpenForObservations())->toBeTrue();
});

it('no acepta el envio de observacion despues de la fecha de termino', function () {
    $consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'vencida-post',
        'starts_at' => now()->subDays(20),
        'ends_at' => now()->subDays(5),
        'auth_methods' => [Consultation::AUTH_GUEST],
    ]);
    ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
        'starts_at' => now()->subDays(20),
        'ends_at' => now()->subDays(5),
    ]);

    $response = $this->post(route('public.observations.store', $consultation), [
        'actor_type' => 'natural',
        'guest_name' => 'Tardia Perez',
        'guest_id_type' => 'rut',
        'guest_national_id' => '12345678-9',
        'guest_email' => 'tardia@example.cl',
        'body' => 'Observacion enviada fuera de plazo que no deberia aceptarse jamas.',
    ]);

    $response->assertSessionHasErrors('body');
    expect(\App\Models\Observation::count())->toBe(0);
});

it('oculta y deshabilita los campos de Razon social / RUT entidad por defecto (Persona Natural)', function () {
    // Issue 2: con Persona Natural preseleccionada, el bloque PJ/Org no debe
    // pedir Razon social ni RUT entidad, aun antes de que corra el JS.
    $consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'form-natural-default',
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->addDays(20),
        'auth_methods' => [Consultation::AUTH_GUEST],
    ]);
    ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
        'starts_at' => now()->subDays(2),
        'ends_at' => now()->addDays(20),
    ]);

    $html = $this->get("/consultas/{$consultation->slug}")->assertOk()->getContent();

    // El bloque PJ/Org se renderiza oculto por defecto; el Natural, visible.
    expect($html)->toMatch('/data-show-for="pj org"\s+style="display:none"/')
        ->and($html)->toMatch('/data-show-for="natural"\s*>/');
    // Sus inputs llegan deshabilitados (no se envian aunque falle el JS).
    // Lookaheads para tolerar el orden de atributos del componente.
    expect($html)->toMatch('/<input\b(?=[^>]*\sdisabled)(?=[^>]*\bid="guest_legal_name")[^>]*>/')
        ->and($html)->toMatch('/<input\b(?=[^>]*\sdisabled)(?=[^>]*\bid="guest_business_id")[^>]*>/');
    // El input de Persona Natural, en cambio, NO esta deshabilitado.
    expect($html)->toMatch('/<input\b(?![^>]*\sdisabled)[^>]*\bid="guest_name"[^>]*>/');
});
