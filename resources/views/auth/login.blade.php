<x-guest-layout>
    <h1 class="h4 mb-4 text-center">Ingreso al backoffice</h1>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Correo institucional" />
            <x-text-input id="email" type="email" name="email" :value="old('email')"
                          required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" value="Contrasena" />
            <x-text-input id="password" type="password" name="password"
                          required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label small text-muted">
                Recordarme en este dispositivo
            </label>
        </div>

        <div class="d-grid">
            <x-primary-button class="btn-lg">
                Ingresar
            </x-primary-button>
        </div>

        @if (Route::has('password.request'))
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="small text-muted">
                    Olvide mi contrasena
                </a>
            </div>
        @endif
    </form>
</x-guest-layout>
