<?php

use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\Observation;
use Illuminate\Support\Facades\Mail;

/**
 * Cobertura del flujo de envio de observaciones tras la incorporacion del
 * selector de tipo de actor (acta GORE junio 2026, punto 3). Tres caminos:
 *  - Persona Natural (guest sin ClaveUnica)
 *  - Persona Juridica (guest)
 *  - Organizacion sin PJ (guest)
 *
 * Tambien valida el invariante del modelo: PJ/Org NUNCA tienen user_id.
 */
beforeEach(function () {
    Mail::fake();

    // Consulta activa con un stage activo que acepta observaciones y admite
    // los dos metodos de auth: claveunica + guest.
    $this->consultation = Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
        'slug' => 'prot-test-2026',
        'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
    ]);
    $this->stage = ConsultationStage::factory()->create([
        'consultation_id' => $this->consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
});

it('acepta observacion de Persona Natural guest con todos los campos', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'natural',
        'guest_name' => 'Ana Lopez',
        'guest_id_type' => 'rut',
        'guest_national_id' => '15555555-K',
        'guest_email' => 'ana@example.cl',
        'guest_phone' => '+56 9 1234 5678',
        'guest_comuna' => 'Vina del Mar',
        'guest_age' => 35,
        'body' => 'Mi observacion sobre el uso de suelo en la zona costera de Concon.',
    ]);

    $response->assertRedirect();
    $obs = Observation::latest()->first();
    expect($obs)->not->toBeNull()
        ->and($obs->snapshot_actor_type)->toBe('natural')
        ->and($obs->snapshot_id_type)->toBe('rut')
        ->and($obs->snapshot_national_id)->toBe('15555555-K')
        ->and($obs->snapshot_full_name)->toBe('Ana Lopez')
        ->and($obs->snapshot_email)->toBe('ana@example.cl')
        ->and($obs->snapshot_phone)->toBe('+56 9 1234 5678')
        ->and($obs->snapshot_comuna)->toBe('Vina del Mar')
        ->and($obs->snapshot_age)->toBe(35)
        ->and($obs->snapshot_legal_name)->toBeNull()
        ->and($obs->snapshot_business_id)->toBeNull()
        ->and($obs->auth_method_used)->toBe('guest')
        ->and($obs->user_id)->toBeNull();
});

it('acepta observacion de Persona Juridica guest', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'pj',
        'guest_legal_name' => 'Constructora ACME SpA',
        'guest_trade_name' => 'ACME',
        'guest_business_id' => '76123456-7',
        'guest_email' => 'contacto@acme.cl',
        'guest_phone' => '+56 2 2345 6789',
        'guest_address' => 'Av. Brasil 1234, Valparaiso',
        'body' => 'Observamos un riesgo en la zona portuaria que afecta la operacion del puerto.',
    ]);

    $response->assertRedirect();
    $obs = Observation::latest()->first();
    expect($obs->snapshot_actor_type)->toBe('pj')
        ->and($obs->snapshot_legal_name)->toBe('Constructora ACME SpA')
        ->and($obs->snapshot_trade_name)->toBe('ACME')
        ->and($obs->snapshot_business_id)->toBe('76123456-7')
        ->and($obs->snapshot_address)->toBe('Av. Brasil 1234, Valparaiso')
        ->and($obs->snapshot_full_name)->toBeNull()
        ->and($obs->snapshot_national_id)->toBeNull()
        ->and($obs->user_id)->toBeNull();
});

it('acepta observacion de Organizacion sin PJ', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'org',
        'guest_legal_name' => 'Junta de Vecinos Cerro Alegre',
        'guest_business_id' => '70123456-K',
        'guest_email' => 'jjvv.cerroalegre@gmail.com',
        'body' => 'Como organizacion vecinal queremos manifestar nuestra preocupacion sobre el plan vial.',
    ]);

    $response->assertRedirect();
    $obs = Observation::latest()->first();
    expect($obs->snapshot_actor_type)->toBe('org')
        ->and($obs->snapshot_legal_name)->toBe('Junta de Vecinos Cerro Alegre')
        ->and($obs->snapshot_business_id)->toBe('70123456-K')
        ->and($obs->user_id)->toBeNull();
});

it('rechaza guest sin actor_type', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'guest_name' => 'Sin tipo',
        'guest_email' => 'sin@tipo.cl',
        'body' => 'Cuerpo de la observacion suficientemente largo.',
    ]);
    $response->assertSessionHasErrors('actor_type');
});

it('rechaza Persona Natural guest sin nombre', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'natural',
        'guest_id_type' => 'rut',
        'guest_national_id' => '11111111-1',
        'guest_email' => 'sinnombre@example.cl',
        'body' => 'Cuerpo suficiente para pasar la validacion de longitud minima.',
    ]);
    $response->assertSessionHasErrors('guest_name');
});

it('rechaza PJ sin razon social', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'pj',
        'guest_business_id' => '76555555-5',
        'guest_email' => 'pj@example.cl',
        'body' => 'Cuerpo suficiente para pasar la validacion de longitud minima.',
    ]);
    $response->assertSessionHasErrors('guest_legal_name');
});

it('rechaza PJ sin RUT de la entidad', function () {
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'pj',
        'guest_legal_name' => 'Empresa sin RUT',
        'guest_email' => 'pj2@example.cl',
        'body' => 'Cuerpo suficiente para pasar la validacion de longitud minima.',
    ]);
    $response->assertSessionHasErrors('guest_business_id');
});

it('rechaza guest si la consulta NO admite guest mode', function () {
    $this->consultation->update(['auth_methods' => [Consultation::AUTH_CLAVEUNICA]]);
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'natural',
        'guest_name' => 'No deberia entrar',
        'guest_id_type' => 'rut',
        'guest_national_id' => '12345678-9',
        'guest_email' => 'rechaza@example.cl',
        'body' => 'Cuerpo suficiente para pasar la validacion de longitud minima.',
    ]);
    // Authorize() falla -> 403
    $response->assertForbidden();
});

it('ClaveUnica autenticado siempre snapshot actor_type=natural', function () {
    $user = \App\Models\User::factory()->citizen()->create([
        'national_id' => '17777777-7',
        'name' => 'Juan',
        'last_name' => 'Perez',
        'email' => 'juan@claveunica.cl',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user);
    session(['auth_method' => 'claveunica']);

    // Aunque el funcionario envie actor_type='pj' por la URL, el controller
    // ignora la entrada para usuarios autenticados y fuerza 'natural'.
    $response = $this->post(route('public.observations.store', $this->consultation), [
        'actor_type' => 'pj',  // <- esto se ignora para auth user
        'body' => 'Observacion enviada desde ClaveUnica con todos los campos validos requeridos.',
    ]);

    $response->assertRedirect();
    $obs = Observation::latest()->first();
    expect($obs->snapshot_actor_type)->toBe('natural')
        ->and($obs->snapshot_national_id)->toBe('17777777-7')
        ->and($obs->snapshot_full_name)->toBe('Juan Perez')
        ->and($obs->user_id)->toBe($user->id)
        ->and($obs->auth_method_used)->toBe('claveunica');
});

it('el modelo lanza LogicException si se crea PJ con user_id', function () {
    expect(fn () => Observation::create([
        'consultation_id' => $this->consultation->id,
        'stage_id' => $this->stage->id,
        'user_id' => \App\Models\User::factory()->citizen()->create()->id,
        'body' => 'body suficiente para pasar validacion del modelo si la tuviera.',
        'auth_method_used' => 'claveunica',
        'snapshot_actor_type' => 'pj',
        'snapshot_legal_name' => 'No deberia permitir esto',
        'snapshot_business_id' => '76000000-0',
        'snapshot_email' => 'pjbug@example.cl',
    ]))->toThrow(\LogicException::class);
});

it('el modelo lanza LogicException si se crea Org con user_id', function () {
    expect(fn () => Observation::create([
        'consultation_id' => $this->consultation->id,
        'stage_id' => $this->stage->id,
        'user_id' => \App\Models\User::factory()->citizen()->create()->id,
        'body' => 'body suficiente.',
        'auth_method_used' => 'claveunica',
        'snapshot_actor_type' => 'org',
        'snapshot_legal_name' => 'No deberia permitir esto tampoco',
        'snapshot_business_id' => '70000000-0',
        'snapshot_email' => 'orgbug@example.cl',
    ]))->toThrow(\LogicException::class);
});
