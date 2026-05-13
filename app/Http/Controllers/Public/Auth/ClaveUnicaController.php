<?php

namespace App\Http\Controllers\Public\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Rut;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Maneja el flujo OIDC Authorization Code + PKCE con ClaveUnica.
 *
 * Soporta dos modos seleccionados por config('claveunica.mode'):
 *
 *  - 'mock' : redirige a /dev/claveunica/simulate, donde un MockClaveUnica
 *             pretende ser el proveedor oficial. Usado en local y QA hasta
 *             que el GORE registre el cliente OIDC en accounts.claveunica.gob.cl
 *  - 'live' : flujo OIDC real contra los endpoints oficiales (config)
 *
 * En ambos modos la salida es la misma: usuario creado/upserted en la BD,
 * sesion abierta y session('auth_method')='claveunica' para que las
 * observaciones siguientes se etiqueten correctamente.
 */
class ClaveUnicaController extends Controller
{
    /**
     * Inicia el flujo: genera state + PKCE pair, los guarda en sesion y
     * redirige al provider (real o mock).
     */
    public function redirect(): RedirectResponse
    {
        $state = Str::random(40);
        $codeVerifier = Str::random(64);
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        session([
            'claveunica.state' => $state,
            'claveunica.code_verifier' => $codeVerifier,
        ]);

        if (config('claveunica.mode') === 'mock') {
            return redirect()->route('mock.claveunica.simulate', [
                'state' => $state,
                'code_challenge' => $codeChallenge,
                'redirect_uri' => route('citizen.claveunica.callback'),
            ]);
        }

        // Modo live: redirige al ClaveUnica oficial
        $url = config('claveunica.authorize_url') . '?' . http_build_query([
            'response_type' => 'code',
            'client_id' => config('claveunica.client_id'),
            'redirect_uri' => route('citizen.claveunica.callback'),
            'scope' => implode(' ', config('claveunica.scopes')),
            'state' => $state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ]);

        return redirect()->away($url);
    }

    /**
     * Callback OIDC: recibe el code, valida state, intercambia code por
     * token + userinfo, y crea/loguea al ciudadano.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->input('state') !== session('claveunica.state')) {
            return redirect()->route('citizen.login')
                ->withErrors(['email' => 'Sesion de ClaveUnica invalida. Intenta nuevamente.']);
        }

        $userInfo = config('claveunica.mode') === 'mock'
            ? $this->fetchUserInfoMock($request)
            : $this->fetchUserInfoLive($request);

        if (! $userInfo || empty($userInfo['run'])) {
            return redirect()->route('citizen.login')
                ->withErrors(['email' => 'No se pudo obtener tu identidad desde ClaveUnica.']);
        }

        $user = $this->upsertUser($userInfo);

        Auth::login($user, remember: true);
        session(['auth_method' => 'claveunica']);
        session()->forget(['claveunica.state', 'claveunica.code_verifier']);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * En modo mock, los datos del usuario se guardan en sesion por el
     * MockClaveUnicaController. Los recuperamos y limpiamos.
     */
    private function fetchUserInfoMock(Request $request): ?array
    {
        $payload = session('claveunica.mock_payload');
        session()->forget('claveunica.mock_payload');
        return $payload;
    }

    /**
     * Flujo real OIDC: code -> token -> userinfo. No probado en produccion
     * mientras no llegen credenciales del GORE (gestion pendiente con Lukas).
     */
    private function fetchUserInfoLive(Request $request): ?array
    {
        $tokenResponse = Http::asForm()->post(config('claveunica.token_url'), [
            'client_id' => config('claveunica.client_id'),
            'client_secret' => config('claveunica.client_secret'),
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'redirect_uri' => route('citizen.claveunica.callback'),
            'code_verifier' => session('claveunica.code_verifier'),
        ]);

        if (! $tokenResponse->successful()) {
            return null;
        }

        $accessToken = $tokenResponse->json('access_token');
        $userResponse = Http::withToken($accessToken)->get(config('claveunica.userinfo_url'));

        if (! $userResponse->successful()) {
            return null;
        }

        $data = $userResponse->json();
        return [
            'run' => $data['RolUnico']['numero'] ?? null,
            'dv' => $data['RolUnico']['DV'] ?? null,
            'name' => $data['name'] ?? ($data['nombres'] ?? ''),
            'last_name' => $data['apellidos'] ?? '',
            'email' => $data['email'] ?? null,
        ];
    }

    /**
     * Crea o actualiza al ciudadano segun el RUN entregado por ClaveUnica.
     * Si el correo no viene en el token (situacion comun en ClaveUnica),
     * usamos un placeholder que el usuario completara en su perfil.
     */
    private function upsertUser(array $info): User
    {
        $nationalId = $info['run'] . (isset($info['dv']) ? '-' . $info['dv'] : '');
        $nationalId = Rut::normalize($nationalId);

        $user = User::firstOrNew(['national_id' => $nationalId]);

        // Solo seteamos atributos cuando ClaveUnica los entrega, para no
        // pisar datos que el usuario haya actualizado manualmente.
        // Usamos asignacion directa porque email_verified_at no esta en
        // fillable (fill() lo ignoraria).
        if (! $user->exists) {
            $user->name = $info['name'] ?? 'Ciudadano';
            $user->last_name = $info['last_name'] ?? '';
            $user->email = $info['email'] ?? "{$nationalId}@claveunica.local";
            $user->password = Str::random(40); // unused — los entrados por ClaveUnica nunca usan password
            $user->role = User::ROLE_CITIZEN;
            $user->is_active = true;
            $user->email_verified_at = now(); // ClaveUnica ya verifico la identidad
        }

        $user->last_login_at = now();
        $user->save();

        return $user;
    }
}
