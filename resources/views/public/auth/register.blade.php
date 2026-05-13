<x-public-layout>
    @section('title', 'Crear cuenta')

    <section class="py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 64px; height: 64px; background: rgba(21,28,104,0.08); color: var(--gore-primary); border-radius: 16px;">
                            <i class="bi bi-person-plus" style="font-size: 2rem;"></i>
                        </div>
                        <h1 class="h2 fw-bold mb-2" style="letter-spacing: -0.02em;">
                            Crear cuenta ciudadana
                        </h1>
                        <p class="text-muted small mb-0">
                            Para enviar observaciones formales a los procesos de consulta del GORE.
                        </p>
                    </div>

                    <div class="alert alert-info small mb-4 d-flex">
                        <i class="bi bi-info-circle me-2 flex-shrink-0" style="font-size: 1.1rem;"></i>
                        <div>
                            <strong>¿Tienes ClaveUnica?</strong> Te recomendamos usarla directamente al participar
                            en una consulta — no necesitas crear esta cuenta. Este flujo manual es un mecanismo
                            alternativo para quienes no cuenten con ClaveUnica.
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" action="{{ route('citizen.register.store') }}">
                                @csrf

                                {{-- Honeypot: campo oculto visualmente; los bots lo completan, humanos no --}}
                                <div style="position: absolute; left: -9999px;" aria-hidden="true">
                                    <label for="website">Si eres humano, deja este campo vacio</label>
                                    <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                                </div>

                                <div class="mb-3">
                                    <x-input-label for="national_id" value="RUT *" />
                                    <x-text-input id="national_id" type="text" name="national_id"
                                                  :value="old('national_id')" required autofocus
                                                  maxlength="12" placeholder="12.345.678-9" />
                                    <div class="form-text">Se acepta con o sin puntos. El digito verificador se valida.</div>
                                    <x-input-error :messages="$errors->get('national_id')" />
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <x-input-label for="name" value="Nombres *" />
                                        <x-text-input id="name" type="text" name="name"
                                                      :value="old('name')" required maxlength="100" />
                                        <x-input-error :messages="$errors->get('name')" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input-label for="last_name" value="Apellidos *" />
                                        <x-text-input id="last_name" type="text" name="last_name"
                                                      :value="old('last_name')" required maxlength="100" />
                                        <x-input-error :messages="$errors->get('last_name')" />
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <x-input-label for="email" value="Correo electronico *" />
                                    <x-text-input id="email" type="email" name="email"
                                                  :value="old('email')" required autocomplete="email" />
                                    <div class="form-text">
                                        Te enviaremos un enlace de verificacion. Sin verificar tu correo no podras enviar observaciones.
                                    </div>
                                    <x-input-error :messages="$errors->get('email')" />
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <x-input-label for="password" value="Contraseña *" />
                                        <x-text-input id="password" type="password" name="password"
                                                      required autocomplete="new-password" />
                                        <div class="form-text">Minimo 8 caracteres.</div>
                                        <x-input-error :messages="$errors->get('password')" />
                                    </div>
                                    <div class="col-md-6">
                                        <x-input-label for="password_confirmation" value="Confirmar contraseña *" />
                                        <x-text-input id="password_confirmation" type="password"
                                                      name="password_confirmation" required autocomplete="new-password" />
                                    </div>
                                </div>

                                <div class="form-check mb-4">
                                    <input class="form-check-input" type="checkbox" name="terms" id="terms" value="1"
                                           @checked(old('terms'))>
                                    <label class="form-check-label small" for="terms">
                                        Acepto los <a href="#" target="_blank">terminos de uso</a> y la
                                        <a href="#" target="_blank">politica de proteccion de datos</a>
                                        del Gobierno Regional de Valparaiso.
                                    </label>
                                    <x-input-error :messages="$errors->get('terms')" />
                                </div>

                                <div class="d-grid">
                                    <x-primary-button class="btn-lg">
                                        Crear cuenta
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-4 small">
                        ¿Ya tienes cuenta?
                        <a href="{{ route('citizen.login') }}" class="fw-semibold text-decoration-none">
                            Iniciar sesion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
