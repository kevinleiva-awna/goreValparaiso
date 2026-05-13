<?php

namespace App\Http\Requests\Public\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginCitizenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Autentica al ciudadano. Espejo del LoginRequest del staff:
     * - aplica throttling por (email, ip)
     * - tras login exitoso, valida que el rol sea 'ciudadano' (los
     *   funcionarios deben usar /admin/login para mantener separados
     *   los flujos)
     * - mensaje generico ante credenciales invalidas (no revela si
     *   el correo existe)
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages([
                'email' => 'Esta cuenta esta desactivada.',
            ]);
        }

        if ($user->role !== User::ROLE_CITIZEN) {
            // Staff intentando entrar por /ingresar: redirigir a /admin/login
            // sin loguearlos aqui, para mantener los flujos separados.
            Auth::guard('web')->logout();
            throw ValidationException::withMessages([
                'email' => 'Tu cuenta es de funcionario. Ingresa por el portal del backoffice.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip()) . '|citizen';
    }
}
