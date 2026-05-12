<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Cambiar contrasena</h2>
        <p class="text-muted small mb-0">
            Usa una contrasena larga y aleatoria para mantener tu cuenta segura.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" value="Contrasena actual" />
            <x-text-input id="update_password_current_password" name="current_password" type="password"
                          autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" value="Nueva contrasena" />
            <x-text-input id="update_password_password" name="password" type="password"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" value="Confirmar nueva contrasena" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password"
                          autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" />
        </div>

        <div class="d-flex align-items-center gap-2">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">Guardado.</span>
            @endif
        </div>
    </form>
</section>
