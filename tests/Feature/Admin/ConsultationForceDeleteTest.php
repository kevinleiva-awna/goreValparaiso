<?php

use App\Models\Consultation;
use App\Models\ConsultationDocument;
use App\Models\InstitutionalResponse;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/*
|--------------------------------------------------------------------------
| Eliminacion definitiva de consultas archivadas
|--------------------------------------------------------------------------
| Pedido de GORE (05-oct-2026): una consulta archivada se puede eliminar con
| todo su expediente, pero solo super-admin, previa pagina que detalla lo que
| se borra y con confirmacion escrita.
*/

/**
 * Consulta archivada con un expediente completo: dos observaciones (una
 * archivada) con adjunto, una respuesta publicada y un antecedente con dos
 * versiones, todos con su archivo en el disk fake.
 */
function consultaArchivadaConExpediente(): Consultation
{
    $consultation = Consultation::factory()->create(['title' => 'Consulta a eliminar']);
    $citizen = User::factory()->citizen()->create();

    foreach ([false, true] as $archivada) {
        $path = "observations/{$consultation->id}/".Str::random(20).'.pdf';
        Storage::disk('local')->put($path, 'adjunto');
        $obs = Observation::factory()->forConsultation($consultation)->byUser($citizen)->create([
            'attachment_path' => $path,
            'attachment_disk' => 'local',
            'attachment_original_name' => 'adjunto.pdf',
            'attachment_size_bytes' => 7,
        ]);
        if ($archivada) {
            $obs->delete();
        } else {
            InstitutionalResponse::factory()->create(['observation_id' => $obs->id, 'status' => InstitutionalResponse::STATUS_PUBLISHED]);
        }
    }

    foreach ([1, 2] as $version) {
        $path = "consultations/{$consultation->id}/grupo/v{$version}/memoria.pdf";
        Storage::disk('local')->put($path, 'antecedente');
        $doc = ConsultationDocument::factory()->create([
            'consultation_id' => $consultation->id,
            'storage_path' => $path,
            'storage_disk' => 'local',
            'version' => $version,
        ]);
        if ($version === 1) {
            $doc->delete();
        }
    }

    $consultation->delete();

    return $consultation;
}

it('detalla lo que se va a borrar antes de eliminar', function () {
    Storage::fake('local');
    actingAsSuperAdmin();
    $consultation = consultaArchivadaConExpediente();

    $this->get(route('admin.consultations.force-delete.confirm', $consultation))
        ->assertOk()
        ->assertSeeText('Consulta a eliminar')
        ->assertSeeText('(incluye 1 archivada(s))')
        ->assertSee('consultation_id='.$consultation->id, false);
});

it('elimina la consulta archivada con todo su expediente y sus archivos', function () {
    Storage::fake('local');
    $admin = actingAsSuperAdmin();
    $consultation = consultaArchivadaConExpediente();
    $archivos = Storage::disk('local')->allFiles();
    expect($archivos)->toHaveCount(4);

    $this->delete(route('admin.consultations.force-delete', $consultation), ['confirmacion' => 'ELIMINAR'])
        ->assertRedirect(route('admin.consultations.index', ['archived' => 1]));

    expect(Consultation::withTrashed()->find($consultation->id))->toBeNull()
        ->and(Observation::withTrashed()->where('consultation_id', $consultation->id)->count())->toBe(0)
        ->and(ConsultationDocument::withTrashed()->where('consultation_id', $consultation->id)->count())->toBe(0)
        ->and(InstitutionalResponse::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);

    $log = Activity::where('log_name', 'consultation')->where('description', 'force_deleted')->sole();
    expect($log->causer_id)->toBe($admin->id)
        ->and($log->properties['attributes']['title'])->toBe('Consulta a eliminar')
        ->and($log->properties['attributes']['observaciones'])->toBe(2)
        ->and($log->properties['attributes']['adjuntos'])->toBe(2);
});

it('exige escribir ELIMINAR para confirmar', function (?string $texto) {
    Storage::fake('local');
    actingAsSuperAdmin();
    $consultation = consultaArchivadaConExpediente();

    $this->from(route('admin.consultations.force-delete.confirm', $consultation))
        ->delete(route('admin.consultations.force-delete', $consultation), ['confirmacion' => $texto])
        ->assertSessionHasErrors('confirmacion');

    expect(Consultation::withTrashed()->find($consultation->id))->not->toBeNull();
})->with([null, 'eliminar', 'BORRAR']);

it('no elimina una consulta que no esta archivada', function () {
    actingAsSuperAdmin();
    $consultation = Consultation::factory()->create();

    $this->get(route('admin.consultations.force-delete.confirm', $consultation))->assertNotFound();
    $this->delete(route('admin.consultations.force-delete', $consultation), ['confirmacion' => 'ELIMINAR'])
        ->assertNotFound();

    expect(Consultation::find($consultation->id))->not->toBeNull();
});

it('solo un super-admin puede eliminar una consulta', function () {
    Storage::fake('local');
    $consultation = consultaArchivadaConExpediente();
    actingAsFunctionary();

    $this->get(route('admin.consultations.force-delete.confirm', $consultation))->assertForbidden();
    $this->delete(route('admin.consultations.force-delete', $consultation), ['confirmacion' => 'ELIMINAR'])
        ->assertForbidden();

    expect(Consultation::withTrashed()->find($consultation->id))->not->toBeNull();
});
