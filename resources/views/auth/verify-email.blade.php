<x-guest-layout>
    <h1 class="h4 mb-3 text-center">Verifica tu correo</h1>

    <p class="text-muted small mb-4">
        Te enviamos un enlace de verificacion a tu correo institucional. Haz click en el enlace para activar tu cuenta. Si no llego, podemos reenviarlo.
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success small">
            Se envio un nuevo enlace de verificacion al correo registrado.
        </div>
    @endif

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <div class="d-grid mb-2">
            <x-primary-button>
                Reenviar enlace de verificacion
            </x-primary-button>
        </div>
    </form>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <div class="d-grid">
            <button type="submit" class="btn btn-link text-muted small">
                Cerrar sesion
            </button>
        </div>
    </form>
</x-guest-layout>
