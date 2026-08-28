<?php

namespace App\Http\Controllers\Public\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\Rut;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Maneja el flujo OIDC Authorization Code con ClaveUnica.
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
 *
 * Referencia normativa: "Manual de Integracion - Guia Tecnica ClaveUnica"
 * v5.5 (enero 2025), Secretaria de Gobierno Digital. El flujo live sigue
 * literalmente sus pasos 2 a 7; desviarse de ellos es motivo de observacion
 * en la certificacion que habilita las credenciales de produccion.
 */
class ClaveUnicaController extends Controller
{
    /**
     * Inicia el flujo: genera el state anti-falsificacion, lo guarda en sesion
     * y redirige al provider (real o mock).
     *
     * No se manda PKCE. ClaveUnica no lo soporta: su documento de descubrimiento
     * (accounts.claveunica.gob.cl/openid/.well-known/openid-configuration) no
     * declara code_challenge_methods_supported y la guia tecnica no lo menciona.
     * Un code_challenge que el IdP ignora no aporta seguridad y agrega un
     * parametro no documentado a una peticion que el equipo certificador revisa.
     * El anti-CSRF exigido por la guia (paso 1) es el state, que si va y se
     * verifica en callback().
     */
    public function redirect(): RedirectResponse
    {
        // ClaveUnica deshabilitada mientras no exista la integracion oficial:
        // la entrada se oculta en el front y aqui se corta por si alguien fuerza
        // la URL. Reactivar con CLAVEUNICA_ENABLED=true.
        abort_unless(config('claveunica.enabled'), 404);

        $state = Str::random(40);

        session(['claveunica.state' => $state]);

        if (config('claveunica.mode') === 'mock') {
            return redirect()->route('mock.claveunica.simulate', [
                'state' => $state,
                'redirect_uri' => route('citizen.claveunica.callback'),
            ]);
        }

        // Modo live: redirige al ClaveUnica oficial
        $url = config('claveunica.authorize_url') . '?' . http_build_query([
            'client_id' => config('claveunica.client_id'),
            'response_type' => 'code',
            'scope' => implode(' ', config('claveunica.scopes')),
            'redirect_uri' => route('citizen.claveunica.callback'),
            'state' => $state,
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
        session()->forget('claveunica.state');
        $request->session()->regenerate();

        return redirect()->intended(route('home'));
    }

    /**
     * Cierra la sesion del ciudadano. Reemplaza al ex-AuthenticatedCitizen
     * SessionController::destroy eliminado en B.1.
     *
     * En modo live el logout es federado: primero se destruye la sesion local
     * y despues hay que cerrar tambien la del IdP. Sin ese segundo paso la
     * sesion en accounts.claveunica.gob.cl sigue viva y el proximo "Ingresar"
     * no vuelve a pedir credenciales.
     *
     * Ese segundo paso NO puede ser un redirect del servidor. El endpoint de
     * logout de ClaveUnica responde 204 No Content, sin cabecera Location:
     * ante una navegacion de primer nivel el navegador se queda donde estaba y
     * al ciudadano no le pasa nada visible. Si vuelve a apretar "Cerrar
     * sesion", el POST llega con el token CSRF de la sesion ya destruida y
     * termina en una pagina 419 — que fue exactamente lo que se observo en
     * staging el 28-ago-2026.
     *
     * Por eso se entrega una pagina intermedia que aplica el "Metodo 2" de la
     * guia tecnica: navegar al endpoint (la peticion viaja con las cookies del
     * IdP y cierra su sesion) y, pasado un momento, volver al home por cuenta
     * propia. El 204 juega a favor: como el navegador no se mueve, el
     * temporizador de la propia pagina sigue vivo para rescatarlo.
     */
    public function logout(Request $request): RedirectResponse
    {
        $wasClaveUnica = session('auth_method') === 'claveunica';

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($wasClaveUnica && config('claveunica.enabled') && config('claveunica.mode') === 'live') {
            return redirect()->route('citizen.claveunica.signing-out');
        }

        return redirect()->route('home');
    }

    /**
     * Pagina intermedia del cierre de sesion federado. Llega sin sesion: la
     * local ya se destruyo en logout(), y lo unico pendiente es cerrar la del
     * IdP. Todo el trabajo lo hace public/js/claveunica-logout.js, porque la
     * CSP del portal no admite scripts inline.
     *
     * Se sigue mandando el parametro `redirect`: si ClaveUnica llega a
     * devolver al ciudadano por su cuenta, aterriza en logoutLanding() y el
     * temporizador nunca alcanza a correr. La pagina funciona igual en los dos
     * escenarios.
     */
    public function signingOut(): View
    {
        return view('public.auth.claveunica-cerrando-sesion', [
            'logoutUrl' => config('claveunica.logout_url') . '?' . http_build_query([
                'redirect' => route('citizen.claveunica.logout'),
            ]),
            'returnUrl' => route('home'),
        ]);
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
     * Flujo real OIDC: code -> token -> userinfo.
     *
     * Ambas llamadas van por POST desde el backend, como exige la guia tecnica
     * (paso 4 y paso 6) y como se pedira demostrar en la certificacion. El
     * `state` viaja tambien en el cuerpo del token porque la guia lo lista
     * entre los parametros del POST, aunque OAuth2 no lo requiera ahi.
     *
     * Los fallos se registran en el log: la primera pasada contra el IdP real
     * es a ciegas, y sin el status y el cuerpo de la respuesta un 400 de
     * redirect_uri mal registrada es indistinguible de un secret erroneo.
     * Nunca se loguea el client_secret ni el access_token.
     */
    private function fetchUserInfoLive(Request $request): ?array
    {
        $tokenResponse = Http::asForm()->post(config('claveunica.token_url'), [
            'client_id' => config('claveunica.client_id'),
            'client_secret' => config('claveunica.client_secret'),
            'redirect_uri' => route('citizen.claveunica.callback'),
            'grant_type' => 'authorization_code',
            'code' => $request->input('code'),
            'state' => $request->input('state'),
        ]);

        if (! $tokenResponse->successful()) {
            Log::warning('ClaveUnica: fallo el intercambio code->token', [
                'status' => $tokenResponse->status(),
                'body' => $tokenResponse->body(),
                'redirect_uri' => route('citizen.claveunica.callback'),
            ]);
            return null;
        }

        $accessToken = $tokenResponse->json('access_token');

        if (! $accessToken) {
            Log::warning('ClaveUnica: el token endpoint respondio sin access_token', [
                'keys' => array_keys((array) $tokenResponse->json()),
            ]);
            return null;
        }

        $userResponse = Http::withToken($accessToken)->post(config('claveunica.userinfo_url'));

        if (! $userResponse->successful()) {
            Log::warning('ClaveUnica: fallo la consulta a userinfo', [
                'status' => $userResponse->status(),
                'body' => $userResponse->body(),
            ]);
            return null;
        }

        return $this->mapUserInfo((array) $userResponse->json());
    }

    /**
     * Traduce el JSON de /openid/userinfo/ al arreglo interno que consume
     * upsertUser(). Forma documentada de la respuesta:
     *
     *   {
     *     "sub": "1234567",
     *     "RolUnico": { "DV": "9", "numero": 12345678, "tipo": "RUN" },
     *     "name": { "apellidos": ["Del Rio", "Gonzalez"],
     *               "nombres":   ["Maria", "Carmen"] }
     *   }
     *
     * Dos cosas que no son obvias y estaban mal resueltas antes:
     * `name` es un objeto con dos arreglos, no un string; y `apellidos` cuelga
     * de `name`, no de la raiz. Tampoco se usa `sub` como llave del registro:
     * la guia es explicita en que el identificador de la persona es
     * RolUnico.numero. El correo no viene en la respuesta documentada — se
     * resuelve con placeholder en upsertUser().
     */
    private function mapUserInfo(array $data): array
    {
        $rolUnico = (array) ($data['RolUnico'] ?? []);
        $nombre = (array) ($data['name'] ?? []);

        return [
            'run' => $rolUnico['numero'] ?? null,
            'dv' => $rolUnico['DV'] ?? null,
            'name' => $this->joinParts($nombre['nombres'] ?? []),
            'last_name' => $this->joinParts($nombre['apellidos'] ?? []),
            'email' => $data['email'] ?? null,
        ];
    }

    /**
     * Une los arreglos de nombres/apellidos en un string. Tolera que llegue un
     * string suelto por si el IdP cambia la forma: preferimos degradar a un
     * nombre imperfecto antes que romper el login del ciudadano.
     */
    private function joinParts(mixed $parts): string
    {
        if (is_string($parts)) {
            return trim($parts);
        }

        if (! is_array($parts)) {
            return '';
        }

        $clean = array_filter(array_map(
            fn ($part) => is_scalar($part) ? trim((string) $part) : '',
            $parts
        ));

        return implode(' ', $clean);
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
            // Recorte defensivo: ClaveUnica devuelve todos los nombres y todos
            // los apellidos inscritos, y users.last_name esta limitado a 100.
            // Un ciudadano con cuatro apellidos no puede quedar sin poder entrar.
            $user->name = mb_substr(($info['name'] ?? '') ?: 'Ciudadano', 0, 255);
            $user->last_name = mb_substr($info['last_name'] ?? '', 0, 100);
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
