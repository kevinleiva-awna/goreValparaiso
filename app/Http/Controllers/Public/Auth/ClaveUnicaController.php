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
        // ClaveUnica deshabilitada mientras no exista la integracion oficial:
        // la entrada se oculta en el front y aqui se corta por si alguien fuerza
        // la URL. Reactivar con CLAVEUNICA_ENABLED=true.
        abort_unless(config('claveunica.enabled'), 404);

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
        abort_unless(config('claveunica.enabled'), 404);

        // Sin sesion previa de ClaveUnica -> redirige al listado publico de
        // consultas con un mensaje, en lugar de mandar al ex-citizen.login
        // (ruta eliminada en B.1).
        if ($request->input('state') !== session('claveunica.state')) {
            return redirect()->route('public.consultations.index')
                ->withErrors(['claveunica' => 'Sesion de ClaveUnica invalida. Intenta nuevamente.']);
        }

        $userInfo = config('claveunica.mode') === 'mock'
            ? $this->fetchUserInfoMock($request)
            : $this->fetchUserInfoLive($request);

        if (! $userInfo || empty($userInfo['run'])) {
            return redirect()->route('public.consultations.index')
                ->withErrors(['claveunica' => 'No se pudo obtener tu identidad desde ClaveUnica.']);
        }

        $user = $this->upsertUser($userInfo);

        Auth::login($user, remember: true);
        session(['auth_method' => 'claveunica']);
        session()->forget(['claveunica.state', 'claveunica.code_verifier']);
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Cierra la sesion del ciudadano. Reemplaza al ex-AuthenticatedCitizen
     * SessionController::destroy eliminado en B.1.
     *
     * En modo live el logout es federado: primero se destruye la sesion local
     * y despues se manda al ciudadano al endpoint de logout de ClaveUnica, que
     * cierra la sesion del IdP y lo devuelve a logoutLanding(). Sin ese segundo
     * salto la sesion en accounts.claveunica.gob.cl sigue viva y el proximo
     * "Ingresar" no vuelve a pedir credenciales.
     */
    public function logout(Request $request): RedirectResponse
    {
        $wasClaveUnica = session('auth_method') === 'claveunica';

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($wasClaveUnica && config('claveunica.enabled') && config('claveunica.mode') === 'live') {
            return redirect()->away(
                config('claveunica.logout_url') . '?' . http_build_query([
                    'redirect' => route('citizen.claveunica.logout'),
                ])
            );
        }

        return redirect()->route('home');
    }

    /**
     * Aterrizaje del logout federado: es la URL que ClaveUnica invoca (GET) al
     * terminar de cerrar la sesion del IdP, y la que se declara como "Logout
     * URI" en la solicitud de credenciales.
     *
     * A proposito NO destruye la sesion local — eso ya ocurrio en logout()
     * antes del salto. Un GET que cierra sesion es accionable desde cualquier
     * sitio de terceros (un <img src> basta), asi que esta ruta se queda en un
     * redirect inocuo y el cierre real vive en el POST con CSRF.
     */
    public function logoutLanding(): RedirectResponse
    {
        return redirect()->route('home');
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
