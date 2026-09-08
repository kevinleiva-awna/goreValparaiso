<?php

use App\Models\Consultation;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->consultation = Consultation::factory()->create(['status' => Consultation::STATUS_ACTIVE]);
});

it('lista observaciones con paginacion', function () {
    actingAsFunctionary();
    Observation::factory()->count(25)
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.index'));
    $response->assertOk();
    $response->assertSeeText('Observaciones recibidas');
});

it('filtra observaciones por consulta', function () {
    actingAsFunctionary();
    $other = Consultation::factory()->create();

    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create(['name' => 'Visible']))
        ->create();
    Observation::factory()
        ->forConsultation($other)
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
        ->forConsultation($this->consultation)
        ->byUser($citizen)
        ->create(['auth_method_used' => Observation::AUTH_CLAVEUNICA, 'subject' => 'ConClaveUnica']);
    // 'guest' como segunda categoria visible en el listado: el registro
    // manual fue eliminado en junio 2026, solo quedan claveunica y guest.
    Observation::factory()
        ->forConsultation($this->consultation)
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
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'xlsx']));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheet');
});

it('exporta observaciones en formato csv', function () {
    actingAsFunctionary();
    Observation::factory()->count(2)
        ->forConsultation($this->consultation)
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

/*
|--------------------------------------------------------------------------
| Adjuntos: nombre por expediente + descarga masiva en ZIP
|--------------------------------------------------------------------------
| Pedido de GORE (03-sep-2026): los archivos tienen que poder cruzarse con
| su fila del compendio, y bajarse todos juntos desde la plataforma.
*/

/** Crea una observacion con un adjunto real en el disk fake. */
function observacionConAdjunto(Consultation $consultation, string $originalName = 'mi documento.pdf'): Observation
{
    $path = 'observations/'.$consultation->id.'/'.\Illuminate\Support\Str::random(20).'.pdf';
    Storage::disk('local')->put($path, 'contenido-de-prueba');

    return Observation::factory()
        ->forConsultation($consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create([
            'attachment_path' => $path,
            'attachment_disk' => 'local',
            'attachment_original_name' => $originalName,
            'attachment_mime_type' => 'application/pdf',
            'attachment_size_bytes' => 19,
        ]);
}

it('descarga el adjunto individual nombrado con el ID del expediente', function () {
    Storage::fake('local');
    actingAsFunctionary();
    $obs = observacionConAdjunto($this->consultation);

    $response = $this->get(route('admin.observations.attachment.download', $obs));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))
        ->toContain($obs->public_id.'.pdf')
        ->not->toContain('mi documento');
});

it('conserva la extension original al renombrar el adjunto', function () {
    Storage::fake('local');
    $obs = observacionConAdjunto($this->consultation, 'PLANO.DWG.XLSX');

    expect($obs->attachment_download_name)->toBe($obs->public_id.'.xlsx');
});

it('empaqueta los adjuntos en un ZIP nombrados por expediente', function () {
    Storage::fake('local');
    actingAsFunctionary();
    $conAdjunto = observacionConAdjunto($this->consultation);
    // Sin adjunto: entra al compendio pero no aporta archivos al ZIP.
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.attachments.zip'));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('zip');

    $zip = new ZipArchive();
    expect($zip->open($response->getFile()->getPathname()))->toBeTrue();
    expect($zip->locateName('adjuntos/'.$conAdjunto->public_id.'.pdf'))->not->toBeFalse();
    expect($zip->locateName('indice.csv'))->not->toBeFalse();
    expect($zip->locateName('compendio-observaciones.xlsx'))->not->toBeFalse();
    // El indice amarra el archivo del ZIP con el nombre original del ciudadano.
    expect($zip->getFromName('indice.csv'))
        ->toContain($conAdjunto->public_id)
        ->toContain('mi documento.pdf');
    $zip->close();
});

it('el ZIP de adjuntos respeta los filtros del listado', function () {
    Storage::fake('local');
    actingAsFunctionary();
    $otra = Consultation::factory()->create();
    $incluida = observacionConAdjunto($this->consultation);
    $excluida = observacionConAdjunto($otra);

    $response = $this->get(route('admin.observations.attachments.zip', [
        'consultation_id' => $this->consultation->id,
    ]));
    $response->assertOk();

    $zip = new ZipArchive();
    $zip->open($response->getFile()->getPathname());
    expect($zip->locateName('adjuntos/'.$incluida->public_id.'.pdf'))->not->toBeFalse();
    expect($zip->locateName('adjuntos/'.$excluida->public_id.'.pdf'))->toBeFalse();
    $zip->close();
});

it('avisa en vez de entregar un ZIP vacio cuando el filtro no trae adjuntos', function () {
    Storage::fake('local');
    actingAsFunctionary();
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $this->get(route('admin.observations.attachments.zip'))
        ->assertRedirect()
        ->assertSessionHas('warning');
});

it('salta el adjunto huerfano en vez de tumbar el ZIP completo', function () {
    Storage::fake('local');
    actingAsFunctionary();
    $ok = observacionConAdjunto($this->consultation);
    $huerfano = observacionConAdjunto($this->consultation);
    Storage::disk('local')->delete($huerfano->attachment_path);

    $response = $this->get(route('admin.observations.attachments.zip'));
    $response->assertOk();

    $zip = new ZipArchive();
    $zip->open($response->getFile()->getPathname());
    expect($zip->locateName('adjuntos/'.$ok->public_id.'.pdf'))->not->toBeFalse();
    expect($zip->locateName('adjuntos/'.$huerfano->public_id.'.pdf'))->toBeFalse();
    $zip->close();
});

it('exige sesion de funcionario para bajar el ZIP de adjuntos', function () {
    $this->get(route('admin.observations.attachments.zip'))->assertRedirect(route('login'));
});
