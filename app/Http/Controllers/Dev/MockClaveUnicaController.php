<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Rules\Rut;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Simulador de ClaveUnica para dev y QA. Pretende ser el portal oficial
 * (accounts.claveunica.gob.cl) pero corre localmente.
 *
 * Las rutas /dev/claveunica/* se registran solo fuera de produccion y con
 * config('claveunica.mode') = 'mock'. El abort_if de cada accion es la segunda
 * linea de defensa: si alguien vuelve a registrar las rutas en produccion por
 * error de configuracion, el controlador igual no responde. Este simulador
 * acepta cualquier RUT y abre sesion con esa identidad — publicado en
 * produccion seria un bypass de autenticacion.
 */
class MockClaveUnicaController extends Controller
{
    /**
     * Pantalla "ingrese a ClaveUnica". Recibe los params del flujo OIDC
     * y los pasa al form para que el redirect mantenga el state correcto.
     */
    public function simulate(Request $request): View
    {
        abort_if(app()->isProduction(), 404);

        return view('dev.claveunica.simulate', [
            'state' => $request->input('state'),
            'redirectUri' => $request->input('redirect_uri'),
        ]);
    }

    /**
     * El "usuario" del simulador completo el form. Guardamos los datos
     * en sesion (analogo a un token issued) y redirigimos al callback
     * de la app con un fake authorization_code.
     */
    public function complete(Request $request): RedirectResponse
    {
        abort_if(app()->isProduction(), 404);

        // redirect_uri llega desde el form por fidelidad con el flujo OIDC, pero
        // NO se usa como destino: redirigir a una URL entregada por el cliente
        // convierte esta ruta en un open redirect. El destino real se resuelve
        // desde el route name.
        $validated = $request->validate([
            'run' => ['required', 'string', new Rut()],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'state' => ['required', 'string'],
            'redirect_uri' => ['nullable', 'string'],
        ]);

        $rut = Rut::normalize($validated['run']);
        [$run, $dv] = explode('-', $rut, 2);

        // Guardar el "userinfo" como si ClaveUnica lo hubiera issued. El
        // ClaveUnicaController::callback lo lee desde la sesion.
        session([
            'claveunica.mock_payload' => [
                'run' => $run,
                'dv' => $dv,
                'name' => $validated['name'],
                'last_name' => $validated['last_name'] ?? '',
                'email' => $validated['email'] ?? null,
            ],
        ]);

        // Redirigimos al callback con un fake code (que el callback ignora
        // en mock mode — usa la sesion directamente)
        return redirect()->route('citizen.claveunica.callback', [
            'code' => 'mock-' . Str::random(16),
            'state' => $validated['state'],
        ]);
    }
}
