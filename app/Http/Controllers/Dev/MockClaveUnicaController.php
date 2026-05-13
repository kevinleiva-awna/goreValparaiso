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
 * Las rutas /dev/claveunica/* solo se registran cuando config('claveunica.mode')
 * es 'mock'. En produccion no existen.
 */
class MockClaveUnicaController extends Controller
{
    /**
     * Pantalla "ingrese a ClaveUnica". Recibe los params del flujo OIDC
     * y los pasa al form para que el redirect mantenga el state correcto.
     */
    public function simulate(Request $request): View
    {
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
        $validated = $request->validate([
            'run' => ['required', 'string', new Rut()],
            'name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'state' => ['required', 'string'],
            'redirect_uri' => ['required', 'string'],
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
        return redirect($validated['redirect_uri'] . '?' . http_build_query([
            'code' => 'mock-' . Str::random(16),
            'state' => $validated['state'],
        ]));
    }
}
