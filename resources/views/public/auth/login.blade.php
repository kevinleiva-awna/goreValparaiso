<x-public-layout>
    @section('title', 'Ingresar')

    <section class="py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-5">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 64px; height: 64px; background: rgba(21,28,104,0.08); color: var(--gore-primary); border-radius: 16px;">
                            <i class="bi bi-person-circle" style="font-size: 2rem;"></i>
                        </div>
                        <h1 class="h2 fw-bold mb-2" style="letter-spacing: -0.02em;">
                            Identificate para participar
                        </h1>
                        <p class="text-muted small mb-0">
                            Tu identidad verificada queda asociada a tus observaciones de forma inalterable.
                        </p>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <div class="d-grid mb-3">
                                <a href="{{ route('citizen.claveunica.redirect') }}"
                                   class="btn btn-primary btn-lg fw-semibold">
                                    <i class="bi bi-shield-check me-2"></i>
                                    Ingresar con ClaveUnica
                                </a>
                            </div>
                            @if (config('claveunica.mode') === 'mock')
                                <p class="text-center small text-warning mb-3">
                                    <i class="bi bi-cone-striped me-1"></i>
                                    Modo desarrollo: usa el simulador local.
                                </p>
                            @endif

                            <div class="position-relative my-4">
                                <hr class="m-0">
                                <span class="position-absolute top-50 start-50 translate-middle px-3 small text-uppercase"
                                      style="background-color: var(--gore-bg-elevated); color: var(--gore-ink-soft); letter-spacing: 0.05em;">
                                    o con tus datos
                                </span>
                            </div>

                            @if (session('status') === 'verification-link-sent')
                                <div class="alert alert-success small">
                                    Te enviamos un nuevo enlace de verificacion al correo registrado.
                                </div>
                            @endif

                            @if (session('status'))
                                @if (session('status') !== 'verification-link-sent')
                                    <div class="alert alert-info small">{{ session('status') }}</div>
                                @endif
                            @endif

                            <form method="POST" action="{{ route('citizen.login.store') }}">
                                @csrf

                                <div class="mb-3">
                                    <x-input-label for="email" value="Correo electronico" />
                                    <x-text-input id="email" type="email" name="email" :value="old('email')"
                                                  required autofocus autocomplete="username" />
                                    <x-input-error :messages="$errors->get('email')" />
                                </div>

                                <div class="mb-3">
                                    <x-input-label for="password" value="Contraseña" />
                                    <x-text-input id="password" type="password" name="password"
                                                  required autocomplete="current-password" />
                                    <x-input-error :messages="$errors->get('password')" />
                                </div>

                                <div class="form-check mb-3">
                                    <input id="remember" type="checkbox" class="form-check-input" name="remember">
                                    <label for="remember" class="form-check-label small text-muted">
                                        Recordarme en este dispositivo
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <x-primary-button class="btn-lg">
                                        Ingresar
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-4 small">
                        ¿Es la primera vez que participas?
                        <a href="{{ route('citizen.register') }}" class="fw-semibold text-decoration-none">
                            Crear cuenta
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
