<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="@yield('meta_description', 'Plataforma de Participacion Ciudadana del Gobierno Regional de Valparaiso. Consultas publicas sobre Instrumentos de Planificacion y Ordenamiento Territorial.')">

    <title>@yield('title', 'Participacion Ciudadana') &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    {{-- Skip link para usuarios de teclado / screen readers. Se hace visible
         al recibir foco (estilo en SCSS via .gore-skip-link:focus). --}}
    <a href="#main-content" class="gore-skip-link visually-hidden-focusable">
        Saltar al contenido principal
    </a>

    {{-- Navbar institucional --}}
    <nav class="navbar navbar-expand-lg gore-navbar" aria-label="Navegacion principal">
        <div class="container">
            <a class="navbar-brand py-0" href="{{ url('/') }}">
                {{-- Logo institucional oficial (Logo_A.png — banner doble:
                     escudo a color + "Gobierno Regional / Region de Valparaiso"
                     + slogan "#Valparaiso Region de Derechos"). Altura limitada
                     a 40px para mantener navbar compacto a la Stripe. --}}
                <x-application-logo background="light" :height="40" />
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false"
                    aria-label="Abrir menu de navegacion">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNav">
                {{-- Menu centrado al estilo Stripe: mx-auto en lugar de me-auto. --}}
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}"
                           href="{{ route('home') }}">Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('public.consultations.*') ? 'active' : '' }}"
                           href="{{ route('public.consultations.index') }}">Consultas</a>
                    </li>
                    @if (request()->routeIs('home'))
                        <li class="nav-item">
                            <a class="nav-link" href="#como-funciona">Como funciona</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#preguntas">Preguntas frecuentes</a>
                        </li>
                    @endif
                </ul>

                <div class="d-flex gap-2 align-items-center">
                    @auth
                        @if (Auth::user()->isStaff())
                            {{-- Funcionario o super-admin: acceso al backoffice --}}
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-grid-1x2 me-1"></i> Ir al backoffice
                            </a>
                        @else
                            {{-- Ciudadano autenticado: dropdown con perfil --}}
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="d-inline-flex align-items-center justify-content-center"
                                          style="width: 28px; height: 28px; background: var(--gore-primary); color: #fff; border-radius: 50%; font-size: 0.75rem; font-weight: 600;">
                                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                    </span>
                                    <span class="d-none d-md-inline">{{ Auth::user()->name }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 240px;">
                                    <li>
                                        <div class="dropdown-item-text px-3 py-2">
                                            <div class="fw-semibold text-truncate" style="color: var(--gore-ink);">
                                                {{ Auth::user()->name }} {{ Auth::user()->last_name }}
                                            </div>
                                            <div class="small text-truncate" style="color: var(--gore-ink-soft);">
                                                {{ Auth::user()->email }}
                                            </div>
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('citizen.logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @endif
                    @else
                        {{-- Sin login: solo ClaveUnica como entrada. El registro
                             manual fue eliminado en junio 2026. La participacion
                             "sin registro" se ofrece dentro del formulario de
                             observacion de cada consulta, no como cuenta. --}}
                        <a href="{{ route('citizen.claveunica.redirect') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-shield-check me-1"></i> Ingresar con ClaveUnica
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main id="main-content" tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="gore-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="mb-3">
                        <x-application-logo background="dark" />
                    </div>
                    <p class="small text-white-50 mb-0" style="max-width: 38ch;">
                        Plataforma oficial de participacion ciudadana para procesos de
                        consulta publica de Instrumentos de Planificacion y Ordenamiento
                        Territorial de la Region de Valparaiso.
                    </p>
                </div>

                <div class="col-md-3">
                    <h3 class="gore-footer-title">Navegacion</h3>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2"><a href="{{ url('/') }}">Inicio</a></li>
                        <li class="mb-2"><a href="{{ route('public.consultations.index') }}">Consultas vigentes</a></li>
                        <li class="mb-2"><a href="#como-funciona">Como funciona</a></li>
                        <li class="mb-2"><a href="#preguntas">Preguntas frecuentes</a></li>
                    </ul>
                </div>

                <div class="col-md-4">
                    <h3 class="gore-footer-title">Contacto institucional</h3>
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-2">
                            <i class="bi bi-geo-alt me-2"></i>
                            Blanco N&deg;1791, Valpara&iacute;so, Chile
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-globe me-2"></i>
                            <a href="https://www.gobiernovalparaiso.cl" target="_blank" rel="noopener">
                                gobiernovalparaiso.cl
                            </a>
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-book me-2"></i>
                            <a href="https://www.gobiernovalparaiso.cl/normasGraficas.php" target="_blank" rel="noopener">
                                Normas gr&aacute;ficas
                            </a>
                        </li>
                        <li>
                            <i class="bi bi-envelope me-2"></i>
                            <a href="mailto:contacto@gorevalparaiso.cl">contacto@gorevalparaiso.cl</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="gore-footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <span>&copy; {{ date('Y') }} Gobierno Regional de Valparaiso. Todos los derechos reservados.</span>
                <span class="d-flex gap-3 align-items-center">
                    <a href="{{ route('login') }}" class="text-decoration-none">
                        <i class="bi bi-shield-lock me-1"></i> Acceso funcionarios
                    </a>
                    <span class="text-white-50">&middot;</span>
                    <span>Desarrollo: <a href="https://awna.cl" target="_blank" rel="noopener">AWNA</a></span>
                </span>
            </div>
        </div>
    </footer>
</body>
</html>
