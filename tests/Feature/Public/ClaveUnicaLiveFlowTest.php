<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

/**
 * Flujo OIDC real contra accounts.claveunica.gob.cl (CLAVEUNICA_MODE=live).
 *
 * Estas pruebas fijan el contrato con el IdP tal como lo define la "Guia
 * Tecnica ClaveUnica" v5.5 de la Secretaria de Gobierno Digital: los
 * parametros exactos de /openid/authorize/, el POST a /openid/token/ y el
 * POST a /openid/userinfo/, y sobre todo la forma anidada de la respuesta de
 * userinfo. El equipo certificador revisa justamente estas llamadas antes de
 * habilitar las credenciales de produccion, y no hay ambiente donde
 * equivocarse gratis: sandbox solo acepta cuatro RUN de prueba.
 */

beforeEach(function () {
    config()->set('claveunica.enabled', true);
    config()->set('claveunica.mode', 'live');
    config()->set('claveunica.client_id', 'client-id-de-prueba');
    config()->set('claveunica.client_secret', 'client-secret-de-prueba');
});

/** Respuesta documentada de /openid/userinfo/ (guia tecnica, paso 6). */
function claveUnicaUserInfo(array $overrides = []): array
{
    return array_replace([
        'sub' => '1234567',
        'RolUnico' => [
            'DV' => '9',
            'numero' => 12345678,
            'tipo' => 'RUN',
        ],
        'name' => [
            'apellidos' => ['Del Rio', 'Gonzalez'],
            'nombres' => ['Maria', 'Carmen'],
        ],
    ], $overrides);
}

function fakeClaveUnicaOk(?array $userInfo = null): void
{
    Http::fake([
        'accounts.claveunica.gob.cl/openid/token/' => Http::response([
            'access_token' => 'access-token-de-prueba',
            'token_type' => 'bearer',
            'expires_in' => 3600,
        ]),
        'accounts.claveunica.gob.cl/openid/userinfo/' => Http::response(
            $userInfo ?? claveUnicaUserInfo()
        ),
    ]);
}

/** Recorre el callback con un state valido ya sembrado en la sesion. */
function volverDeClaveUnica(string $code = 'authorization-code-de-prueba')
{
    return test()->withSession(['claveunica.state' => 'state-de-prueba'])
        ->get(route('citizen.claveunica.callback', [
            'code' => $code,
            'state' => 'state-de-prueba',
        ]));
}

it('arma la URL de authorize con los parametros que exige la guia tecnica', function () {
    $response = $this->get(route('citizen.claveunica.redirect'));

    $target = $response->headers->get('Location');
    expect($target)->toStartWith('https://accounts.claveunica.gob.cl/openid/authorize/?');

    parse_str(parse_url($target, PHP_URL_QUERY), $params);

    expect($params['client_id'])->toBe('client-id-de-prueba')
        ->and($params['response_type'])->toBe('code')
        ->and($params['scope'])->toBe('openid run name')
        ->and($params['redirect_uri'])->toBe(route('citizen.claveunica.callback'))
        ->and($params['state'])->toBe(session('claveunica.state'))
        ->and($params['state'])->not->toBeEmpty();
});

it('no manda PKCE, que ClaveUnica no soporta ni documenta', function () {
    $response = $this->get(route('citizen.claveunica.redirect'));

    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $params);

    expect($params)->not->toHaveKey('code_challenge')
        ->and($params)->not->toHaveKey('code_challenge_method');
});

it('cambia el code por el token con un POST de formulario al endpoint oficial', function () {
    fakeClaveUnicaOk();

    volverDeClaveUnica();

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://accounts.claveunica.gob.cl/openid/token/') {
            return false;
        }

        return $request->method() === 'POST'
            && $request['client_id'] === 'client-id-de-prueba'
            && $request['client_secret'] === 'client-secret-de-prueba'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'authorization-code-de-prueba'
            && $request['state'] === 'state-de-prueba'
            && $request['redirect_uri'] === route('citizen.claveunica.callback');
    });
});

it('consulta userinfo por POST con el access token como Bearer', function () {
    fakeClaveUnicaOk();

    volverDeClaveUnica();

    Http::assertSent(function ($request) {
        if ($request->url() !== 'https://accounts.claveunica.gob.cl/openid/userinfo/') {
            return false;
        }

        return $request->method() === 'POST'
            && $request->hasHeader('Authorization', 'Bearer access-token-de-prueba');
    });
});

it('crea al ciudadano desde la respuesta anidada de userinfo', function () {
    fakeClaveUnicaOk();

    $response = volverDeClaveUnica();

    $response->assertRedirect(route('home'));

    $user = User::where('national_id', '12345678-9')->sole();

    // name es un objeto con dos arreglos; los nombres y apellidos se
    // concatenan en su orden de inscripcion.
    expect($user->name)->toBe('Maria Carmen')
        ->and($user->last_name)->toBe('Del Rio Gonzalez')
        ->and($user->role)->toBe(User::ROLE_CITIZEN)
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull();

    $this->assertAuthenticatedAs($user);
    expect(session('auth_method'))->toBe('claveunica');
});

it('usa el RUN y no el sub como llave del registro', function () {
    // La guia es explicita: el campo sub no debe utilizarse como llave del
    // registro; el identificador de la persona es RolUnico.numero.
    $existente = User::factory()->citizen()->create([
        'national_id' => '12345678-9',
        'name' => 'Nombre Ya Corregido',
    ]);

    fakeClaveUnicaOk();

    volverDeClaveUnica();

    expect(User::where('national_id', '12345678-9')->count())->toBe(1);

    // Un reingreso no pisa datos que el ciudadano haya ajustado despues.
    expect($existente->fresh()->name)->toBe('Nombre Ya Corregido');
    $this->assertAuthenticatedAs($existente);
});

it('deja un correo placeholder cuando ClaveUnica no entrega email', function () {
    // La respuesta documentada de userinfo no incluye email.
    fakeClaveUnicaOk();

    volverDeClaveUnica();

    expect(User::where('national_id', '12345678-9')->sole()->email)
        ->toBe('12345678-9@claveunica.local');
});

it('recorta apellidos largos para no reventar la columna de 100', function () {
    fakeClaveUnicaOk(claveUnicaUserInfo([
        'name' => [
            'apellidos' => [str_repeat('Apellido', 20)],
            'nombres' => ['Maria'],
        ],
    ]));

    volverDeClaveUnica();

    expect(mb_strlen(User::where('national_id', '12345678-9')->sole()->last_name))
        ->toBe(100);
});

it('rechaza el callback cuando el state no coincide y no llama al IdP', function () {
    Http::fake();

    $response = $this->withSession(['claveunica.state' => 'state-de-prueba'])
        ->get(route('citizen.claveunica.callback', [
            'code' => 'authorization-code-de-prueba',
            'state' => 'state-falsificado',
        ]));

    $response->assertRedirect(route('public.consultations.index'));
    $response->assertSessionHasErrors('claveunica');
    $this->assertGuest();

    Http::assertNothingSent();
});

it('no abre sesion si el token endpoint falla', function () {
    Http::fake([
        'accounts.claveunica.gob.cl/openid/token/' => Http::response(
            ['error' => 'invalid_grant'], 400
        ),
    ]);

    $response = volverDeClaveUnica('authorization-code-vencido');

    $response->assertRedirect(route('public.consultations.index'));
    $response->assertSessionHasErrors('claveunica');
    $this->assertGuest();
});

it('no abre sesion si userinfo no trae RUN', function () {
    fakeClaveUnicaOk(['sub' => '1234567']);

    $response = volverDeClaveUnica();

    $response->assertRedirect(route('public.consultations.index'));
    $response->assertSessionHasErrors('claveunica');
    $this->assertGuest();
});
