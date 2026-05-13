<?php

namespace App\Http\Controllers\Public\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    /**
     * Pantalla informativa que se muestra a un ciudadano logueado pero
     * con email_verified_at null. Lo invita a revisar su correo.
     */
    public function notice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('home'))
            : view('public.auth.verify-email');
    }

    /**
     * Endpoint al que apunta el link del mail. Laravel valida que la
     * URL este firmada y que el hash coincida con el email del usuario.
     */
    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('home') . '?verificado=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended(route('home') . '?verificado=1');
    }

    /**
     * Reenvio del correo de verificacion (button en la pantalla notice).
     */
    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('home'));
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
