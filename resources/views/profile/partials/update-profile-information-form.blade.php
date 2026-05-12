<section>
    <header class="mb-3">
        <h2 class="h5 mb-1">Informacion del perfil</h2>
        <p class="text-muted small mb-0">
            Actualiza tu nombre y correo institucional.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)"
                          required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div class="mb-3">
            <x-input-label for="email" value="Correo institucional" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)"
                          required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="alert alert-warning small mt-2 mb-0 py-2">
                    Tu correo no esta verificado.
                    <button form="send-verification" class="btn btn-link p-0 small align-baseline">
                        Reenviar enlace de verificacion
                    </button>
                    @if (session('status') === 'verification-link-sent')
                        <div class="text-success mt-1">Se envio un nuevo enlace al correo registrado.</div>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            <x-primary-button>Guardar</x-primary-button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">Guardado.</span>
            @endif
        </div>
    </form>
</section>
