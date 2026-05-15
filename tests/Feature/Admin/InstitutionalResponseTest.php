<?php

use App\Mail\InstitutionalResponsePublished;
use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
});

/**
 * Helper: crea consulta activa, etapa activa, ciudadano y observacion lista
 * para recibir respuesta.
 */
function makeObservation(?Consultation $consultation = null): Observation
{
    $consultation ??= Consultation::factory()->create([
        'status' => Consultation::STATUS_ACTIVE,
    ]);
    $stage = ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
    $citizen = User::factory()->citizen()->create();

    return Observation::factory()
        ->forConsultation($consultation, $stage)
        ->byUser($citizen)
        ->create();
}

it('permite a un funcionario crear una respuesta como borrador', function () {
    actingAsFunctionary();
    $obs = makeObservation();

    $response = $this->post(route('admin.observations.response.store', $obs), [
        'content' => 'Estimado(a) ciudadano(a), su observacion sera evaluada por el equipo tecnico.',
        'publish' => '0',
    ]);

    $response->assertRedirect(route('admin.observations.show', $obs));
    expect($obs->refresh()->response)->not->toBeNull();
    expect($obs->response->status)->toBe(InstitutionalResponse::STATUS_DRAFT);
    expect($obs->response->published_at)->toBeNull();
    Mail::assertNothingQueued();
});

it('permite a un funcionario publicar directamente al crear', function () {
    actingAsFunctionary();
    $obs = makeObservation();

    $this->post(route('admin.observations.response.store', $obs), [
        'content' => 'Respuesta institucional publicada de inmediato. Detalles tecnicos a continuacion.',
        'publish' => '1',
    ])->assertRedirect();

    $response = $obs->refresh()->response;
    expect($response->status)->toBe(InstitutionalResponse::STATUS_PUBLISHED);
    expect($response->published_at)->not->toBeNull();
    Mail::assertQueued(InstitutionalResponsePublished::class, function ($mail) use ($obs) {
        return $mail->hasTo($obs->snapshot_email);
    });
});

it('permite editar un borrador pero no una respuesta publicada', function () {
    $functionary = actingAsFunctionary();
    $obs = makeObservation();
    $draft = InstitutionalResponse::factory()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
        'content' => 'Texto original del borrador.',
    ]);

    $this->put(route('admin.observations.response.update', $obs), [
        'content' => 'Texto actualizado del borrador en el segundo intento de redaccion.',
    ])->assertRedirect();

    expect($draft->refresh()->content)->toContain('actualizado');

    // Ahora publicamos y verificamos que ya no se puede editar.
    $draft->update([
        'status' => InstitutionalResponse::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $this->put(route('admin.observations.response.update', $obs), [
        'content' => 'Intento de edicion fraudulenta post-publicacion del texto.',
    ])->assertStatus(422);

    expect($draft->refresh()->content)->not->toContain('fraudulenta');
});

it('publica un borrador y notifica al ciudadano por correo', function () {
    $functionary = actingAsFunctionary();
    $obs = makeObservation();
    InstitutionalResponse::factory()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
    ]);

    $this->post(route('admin.observations.response.publish', $obs))
        ->assertRedirect();

    expect($obs->refresh()->response->isPublished())->toBeTrue();
    Mail::assertQueued(InstitutionalResponsePublished::class, function ($mail) use ($obs) {
        return $mail->hasTo($obs->snapshot_email);
    });
});

it('rechaza crear respuesta si la observacion ya tiene una', function () {
    $functionary = actingAsFunctionary();
    $obs = makeObservation();
    InstitutionalResponse::factory()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
    ]);

    $this->post(route('admin.observations.response.store', $obs), [
        'content' => 'Intento de duplicar la respuesta, no debe permitirse jamas.',
    ])->assertStatus(422);
});

it('permite descartar un borrador pero no una respuesta publicada', function () {
    $functionary = actingAsFunctionary();
    $obs = makeObservation();
    $draft = InstitutionalResponse::factory()->create([
        'observation_id' => $obs->id,
        'responded_by' => $functionary->id,
    ]);

    $this->delete(route('admin.observations.response.destroy', $obs))
        ->assertRedirect();
    expect(InstitutionalResponse::find($draft->id))->toBeNull();

    // Publicada: no se puede borrar
    $obs2 = makeObservation();
    $published = InstitutionalResponse::factory()->published()->create([
        'observation_id' => $obs2->id,
        'responded_by' => $functionary->id,
    ]);
    $this->delete(route('admin.observations.response.destroy', $obs2))
        ->assertStatus(422);
    expect(InstitutionalResponse::find($published->id))->not->toBeNull();
});

it('crea respuestas en lote compartiendo batch_id y notifica a cada ciudadano', function () {
    actingAsFunctionary();
    $consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
    $stage = ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);
    $observations = collect([1, 2, 3])->map(function () use ($consultation, $stage) {
        $citizen = User::factory()->citizen()->create();
        return Observation::factory()
            ->forConsultation($consultation, $stage)
            ->byUser($citizen)
            ->create();
    });

    $this->post(route('admin.observations.batch.store'), [
        'content' => 'Texto unico de respuesta en lote para tres observaciones similares.',
        'observation_ids' => $observations->pluck('id')->all(),
    ])->assertRedirect();

    $responses = InstitutionalResponse::query()
        ->whereIn('observation_id', $observations->pluck('id'))
        ->get();

    expect($responses)->toHaveCount(3);
    expect($responses->pluck('batch_id')->unique())->toHaveCount(1);
    expect($responses->first()->batch_id)->not->toBeNull();
    $responses->each(function (InstitutionalResponse $r) {
        expect($r->isPublished())->toBeTrue();
    });
    Mail::assertQueued(InstitutionalResponsePublished::class, 3);
});

it('rechaza el lote si alguna observacion ya tiene respuesta', function () {
    $functionary = actingAsFunctionary();
    $consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
    $stage = ConsultationStage::factory()->create([
        'consultation_id' => $consultation->id,
        'status' => ConsultationStage::STATUS_ACTIVE,
        'accepts_observations' => true,
    ]);

    $obsLimpia = Observation::factory()
        ->forConsultation($consultation, $stage)
        ->byUser(User::factory()->citizen()->create())
        ->create();
    $obsConRespuesta = Observation::factory()
        ->forConsultation($consultation, $stage)
        ->byUser(User::factory()->citizen()->create())
        ->create();
    InstitutionalResponse::factory()->create([
        'observation_id' => $obsConRespuesta->id,
        'responded_by' => $functionary->id,
    ]);

    $this->post(route('admin.observations.batch.store'), [
        'content' => 'Intento de respuesta en lote con una observacion conflictiva.',
        'observation_ids' => [$obsLimpia->id, $obsConRespuesta->id],
    ])->assertSessionHasErrors('observation_ids');

    expect($obsLimpia->refresh()->response)->toBeNull();
});

it('rechaza acceso de ciudadanos a las rutas de respuesta admin', function () {
    actingAsCitizen();
    $obs = makeObservation();

    $this->post(route('admin.observations.response.store', $obs), [
        'content' => 'Intento desde ciudadano debe fallar con 403.',
    ])->assertForbidden();

    $this->get(route('admin.observations.batch.create'))->assertForbidden();
});
