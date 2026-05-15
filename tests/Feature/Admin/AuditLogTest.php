<?php

use App\Models\Consultation;
use App\Models\ConsultationStage;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

/**
 * Audit log inmutable (D20) — cumplimiento D.S. 7/2023.
 *
 * Cubre que las acciones criticas del dominio queden registradas en la
 * tabla activity_log con el causer correcto y SIN exponer campos sensibles.
 */

it('registra creacion de Consultation con causer y campos auditados', function () {
    $functionary = actingAsFunctionary();

    $consultation = Consultation::factory()->create([
        'title' => 'Consulta a auditar',
        'status' => Consultation::STATUS_DRAFT,
        'created_by' => $functionary->id,
    ]);

    $activity = Activity::where('subject_type', Consultation::class)
        ->where('subject_id', $consultation->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('consultation');
    expect($activity->event)->toBe('created');
    expect($activity->properties->get('attributes')['title'])->toBe('Consulta a auditar');
});

it('registra cambios de estado de Consultation y omite campos no auditados', function () {
    actingAsFunctionary();
    $consultation = Consultation::factory()->create(['status' => Consultation::STATUS_DRAFT]);

    $consultation->update([
        'status' => Consultation::STATUS_ACTIVE,
        'description' => 'Descripcion nueva que NO debe estar en el log',
    ]);

    $update = Activity::where('subject_type', Consultation::class)
        ->where('subject_id', $consultation->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($update)->not->toBeNull();
    expect($update->properties->get('attributes'))->toHaveKey('status');
    expect($update->properties->get('attributes')['status'])->toBe(Consultation::STATUS_ACTIVE);
    expect($update->properties->get('attributes'))->not->toHaveKey('description');
});

it('NO registra cambios de password en User', function () {
    actingAsSuperAdmin();
    $target = User::factory()->functionary()->create();

    $countBefore = Activity::where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->count();

    $target->update(['password' => bcrypt('nuevo-secreto-NUNCA-loggeado')]);

    $countAfter = Activity::where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->count();

    expect($countAfter)->toBe($countBefore);
});

it('registra cambios de rol e is_active de User', function () {
    actingAsSuperAdmin();
    $target = User::factory()->functionary()->create();

    $target->update(['is_active' => false, 'role' => User::ROLE_CITIZEN]);

    $update = Activity::where('subject_type', User::class)
        ->where('subject_id', $target->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($update)->not->toBeNull();
    $attrs = $update->properties->get('attributes');
    expect($attrs)->toHaveKey('is_active');
    expect($attrs)->toHaveKey('role');
    expect($attrs)->not->toHaveKey('password');
    expect($attrs)->not->toHaveKey('national_id');
});

it('registra creacion de Observation con campos publicos solamente', function () {
    actingAsCitizen();
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

    $activity = Activity::where('subject_type', Observation::class)
        ->where('subject_id', $obs->id)
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->event)->toBe('created');

    $attrs = $activity->properties->get('attributes');
    expect($attrs)->toHaveKey('public_id');
    expect($attrs)->not->toHaveKey('snapshot_national_id');
    expect($attrs)->not->toHaveKey('snapshot_email');
    expect($attrs)->not->toHaveKey('ip_address');
});

it('solo registra creacion de Observation y no updates posteriores', function () {
    actingAsCitizen();
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

    // Forzamos un update (que en produccion no deberia ocurrir, pero validamos
    // que el LogsActivity respete $recordEvents=['created']).
    $obs->update(['subject' => 'Asunto modificado fraudulentamente']);

    $countOfUpdates = Activity::where('subject_type', Observation::class)
        ->where('subject_id', $obs->id)
        ->where('event', 'updated')
        ->count();

    expect($countOfUpdates)->toBe(0);
});

it('expone /admin/activity-log solo a super-admin', function () {
    // Funcionario sin permiso de super-admin -> 403
    actingAsFunctionary();
    $this->get(route('admin.activity-log.index'))->assertForbidden();

    // Super-admin -> 200
    actingAsSuperAdmin();
    $this->get(route('admin.activity-log.index'))->assertOk();
});

it('registra creacion y publicacion de InstitutionalResponse', function () {
    $functionary = actingAsFunctionary();
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

    $response = InstitutionalResponse::create([
        'observation_id' => $obs->id,
        'content' => 'Contenido de la respuesta a auditar.',
        'responded_by' => $functionary->id,
        'responded_at' => now(),
        'status' => InstitutionalResponse::STATUS_DRAFT,
    ]);

    $response->update([
        'status' => InstitutionalResponse::STATUS_PUBLISHED,
        'published_at' => now(),
    ]);

    $logs = Activity::where('subject_type', InstitutionalResponse::class)
        ->where('subject_id', $response->id)
        ->orderBy('id')
        ->get();

    expect($logs)->toHaveCount(2);
    expect($logs->first()->event)->toBe('created');
    expect($logs->last()->event)->toBe('updated');
    expect($logs->last()->properties->get('attributes')['status'])
        ->toBe(InstitutionalResponse::STATUS_PUBLISHED);
});
