<x-guest-layout>
    <h1 class="h4 mb-3 text-center">Confirmar contrasena</h1>

    <p class="text-muted small mb-4">
        Esta es una zona protegida. Confirma tu contrasena para continuar.
    </p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="password" value="Contrasena" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="d-grid">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
