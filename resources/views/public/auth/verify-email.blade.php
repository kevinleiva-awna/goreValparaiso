<x-public-layout>
    @section('title', 'Verifica tu correo')

    <section class="py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5 text-center">
                            <div class="d-inline-flex align-items-center justify-content-center mb-3"
                                 style="width: 72px; height: 72px; background: rgba(16,185,129,0.10); color: var(--gore-success); border-radius: 16px;">
                                <i class="bi bi-envelope-check" style="font-size: 2.25rem;"></i>
                            </div>

                            <h1 class="h3 fw-bold mb-2" style="letter-spacing: -0.02em;">
                                Revisa tu correo electronico
                            </h1>
                            <p class="text-muted mb-0">
                                Te enviamos un enlace de verificacion a
                                <strong style="color: var(--gore-ink);">{{ Auth::user()->email }}</strong>.
                                Haz click en el enlace para activar tu cuenta y poder enviar observaciones.
                            </p>

                            @if (session('status') === 'verification-link-sent')
                                <div class="alert alert-success small mt-4 mb-0">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Te enviamos un nuevo enlace al correo registrado.
                                </div>
                            @endif

                            <hr class="my-4">

                            <p class="small text-muted mb-3">
                                Si no llego el correo, revisa la carpeta de spam o solicita un reenvio.
                            </p>

                            <div class="d-grid gap-2">
                                <form method="POST" action="{{ route('citizen.verification.resend') }}">
                                    @csrf
                                    <x-primary-button class="w-100">
                                        <i class="bi bi-arrow-clockwise me-1"></i>
                                        Reenviar enlace de verificacion
                                    </x-primary-button>
                                </form>

                                <form method="POST" action="{{ route('citizen.logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-link text-muted small">
                                        Cerrar sesion
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <p class="text-center small text-muted mt-4 mb-0">
                        ¿Cambiaste tu correo por error? <a href="{{ route('citizen.logout') }}" onclick="event.preventDefault(); document.querySelector('form[action=\'{{ route('citizen.logout') }}\']').submit();">Cerrar sesion</a>
                        y registrate de nuevo.
                    </p>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
