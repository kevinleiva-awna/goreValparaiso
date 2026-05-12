<x-guest-layout>
    <h1 class="h4 mb-3 text-center">Recuperar contrasena</h1>

    <p class="text-muted small mb-4">
        Ingresa el correo institucional asociado a tu cuenta y te enviaremos un enlace para restablecer la contrasena.
    </p>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="email" value="Correo institucional" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="d-grid">
            <x-primary-button>
                Enviar enlace de recuperacion
            </x-primary-button>
        </div>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="small text-muted">
                Volver al inicio de sesion
            </a>
        </div>
    </form>
</x-guest-layout>
