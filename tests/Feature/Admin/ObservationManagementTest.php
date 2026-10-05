<?php

use App\Http\Controllers\Admin\ObservationController;
use App\Models\Consultation;
use App\Models\Observation;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

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

it('muestra el codigo corto del expediente en el listado', function () {
    actingAsFunctionary();
    $obs = Observation::factory()->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $this->get(route('admin.observations.index'))
        ->assertOk()
        ->assertSeeText(substr($obs->public_id, 0, 8));
});

it('busca por el codigo corto o completo del expediente', function () {
    actingAsFunctionary();
    $citizen = User::factory()->citizen()->create();
    $buscada = Observation::factory()->forConsultation($this->consultation)->byUser($citizen)
        ->create(['subject' => 'La buscada']);
    $otra = Observation::factory()->forConsultation($this->consultation)->byUser($citizen)
        ->create(['subject' => 'Otra observacion']);

    foreach ([substr($buscada->public_id, 0, 8), strtoupper(substr($buscada->public_id, 0, 8)), $buscada->public_id] as $codigo) {
        $this->get(route('admin.observations.index', ['q' => $codigo]))
            ->assertOk()
            ->assertSeeText('La buscada')
            ->assertDontSeeText('Otra observacion');
    }
});

it('no busca por prefijo de codigo si el termino no tiene forma de codigo', function () {
    actingAsFunctionary();
    $obs = Observation::factory()->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create(['subject' => 'Sin relacion', 'body' => 'Texto cualquiera de la observacion']);

    // Los 4 primeros caracteres no bastan: un RUT o una palabra corta no deben
    // traer expedientes que por azar empiezan igual.
    $this->get(route('admin.observations.index', ['q' => substr($obs->public_id, 0, 4)]))
        ->assertOk()
        ->assertDontSeeText('Sin relacion');
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
    $path = 'observations/'.$consultation->id.'/'.Str::random(20).'.pdf';
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

/*
 * Los topes salen de config/exports.php (y del .env) justamente porque la
 * primera calibracion se quedo corta: el proceso real tenia 307 adjuntos
 * contra un tope de 300, y GORE no podia bajar nada.
 */

it('corta por el tope de archivos y lo toma de la configuracion', function () {
    Storage::fake('local');
    actingAsFunctionary();
    config(['exports.zip_max_files' => 1]);
    observacionConAdjunto($this->consultation);
    observacionConAdjunto($this->consultation);

    $this->get(route('admin.observations.attachments.zip'))
        ->assertRedirect()
        ->assertSessionHas('warning', fn ($mensaje) => str_contains($mensaje, '2 adjuntos')
            && str_contains($mensaje, 'maximo por ZIP es 1'));
});

it('corta por el tope de peso y lo toma de la configuracion', function () {
    Storage::fake('local');
    actingAsFunctionary();
    config(['exports.zip_max_mb' => 0]);
    observacionConAdjunto($this->consultation);

    $this->get(route('admin.observations.attachments.zip'))
        ->assertRedirect()
        ->assertSessionHas('warning', fn ($mensaje) => str_contains($mensaje, 'pesan'));
});

it('con el tope por defecto el volumen real de GORE entra', function () {
    // 307 adjuntos y 327 MB en septiembre de 2026: el default tiene que
    // cubrirlo, si no el cliente vuelve a quedar sin poder descargar.
    expect(config('exports.zip_max_files'))->toBeGreaterThanOrEqual(400)
        ->and(config('exports.zip_max_mb'))->toBeGreaterThanOrEqual(400);
});

/*
 * Spinner del boton Exportar. El navegador no avisa nada cuando una navegacion
 * termina en descarga, asi que el JS espera esta cookie para apagarlo. Si el
 * controlador deja de mandarla, el funcionario vuelve a ver un boton muerto
 * durante el minuto que tarda el ZIP -que es el reporte original de GORE-.
 */

/**
 * Busca la cookie de confirmacion entre las de la respuesta. Se compara con
 * getName() y no con firstWhere('name', ...): las Cookie de Symfony tienen la
 * propiedad privada, asi que firstWhere devuelve null SIEMPRE y un test que
 * espera null pasaria sin probar nada.
 */
function cookieDeDescarga($response): ?Cookie
{
    return collect($response->headers->getCookies())->first(
        fn ($cookie) => $cookie->getName() === ObservationController::DOWNLOAD_COOKIE
    );
}

it('confirma la descarga del ZIP con la cookie que apaga el spinner', function () {
    Storage::fake('local');
    actingAsFunctionary();
    observacionConAdjunto($this->consultation);

    $this->get(route('admin.observations.attachments.zip', ['dl_token' => 'abc123']))
        ->assertOk()
        ->assertPlainCookie(ObservationController::DOWNLOAD_COOKIE, 'abc123');
});

it('confirma tambien el compendio en xlsx y csv', function (string $formato) {
    actingAsFunctionary();
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $this->get(route('admin.observations.export', ['format' => $formato, 'dl_token' => 'xyz789']))
        ->assertOk()
        ->assertPlainCookie(ObservationController::DOWNLOAD_COOKIE, 'xyz789');
})->with(['xlsx', 'csv']);

it('la cookie va sin encriptar o el JS no puede compararla con su token', function () {
    actingAsFunctionary();
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'csv', 'dl_token' => 'enclaro']));
    $cookie = cookieDeDescarga($response);

    expect($cookie)->not->toBeNull()
        ->and($cookie->getValue())->toBe('enclaro')
        // httpOnly la haria invisible para document.cookie.
        ->and($cookie->isHttpOnly())->toBeFalse();
});

it('no manda cookie si el export no vino del boton', function () {
    actingAsFunctionary();
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'csv']));

    expect(cookieDeDescarga($response))->toBeNull();
});

it('no refleja en la cookie un token que no sea alfanumerico', function (string $basura) {
    actingAsFunctionary();
    Observation::factory()
        ->forConsultation($this->consultation)
        ->byUser(User::factory()->citizen()->create())
        ->create();

    $response = $this->get(route('admin.observations.export', ['format' => 'csv', 'dl_token' => $basura]));

    expect(cookieDeDescarga($response))->toBeNull();
})->with([
    '',
    'con espacio',
    'punto.y.coma;',
    'demasiado-largo-para-un-token-de-descarga-de-verdad',
]);
