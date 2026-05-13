<?php

namespace App\Http\Controllers\Public\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\Auth\LoginCitizenRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedCitizenSessionController extends Controller
{
    public function create(): View
    {
        return view('public.auth.login');
    }

    public function store(LoginCitizenRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        // Guardar metodo de auth en sesion -> se inyecta como auth_method_used
        // en cada observacion. T4.2 (ClaveUnica) lo sobrescribira a 'claveunica'.
        session(['auth_method' => 'manual']);

        // Si el usuario fue redirigido aqui desde una ruta protegida,
        // intended() lo lleva de vuelta. Si no, va al inicio.
        return redirect()->intended(route('home', absolute: false));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
