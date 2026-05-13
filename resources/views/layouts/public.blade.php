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
    {{-- Navbar institucional --}}
    <nav class="navbar navbar-expand-lg gore-navbar">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                <x-application-logo background="light" />
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                    data-bs-target="#publicNav" aria-controls="publicNav" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNav">
                <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0">
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
                                            @unless (Auth::user()->hasVerifiedEmail())
                                                <span class="gore-badge gore-badge-warning mt-2">
                                                    <i class="bi bi-exclamation-triangle me-1" style="font-size: 0.6rem;"></i>
                                                    Correo sin verificar
                                                </span>
                                            @endunless
                                        </div>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    @unless (Auth::user()->hasVerifiedEmail())
                                        <li>
                                            <a class="dropdown-item" href="{{ route('citizen.verification.notice') }}">
                                                <i class="bi bi-envelope-check me-2"></i>Verificar correo
                                            </a>
                                        </li>
                                    @endunless
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
                        <a href="{{ route('citizen.login') }}" class="btn btn-outline-secondary btn-sm d-none d-sm-inline-block">
                            Ingresar
                        </a>
                        <a href="{{ route('citizen.register') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-person-plus me-1"></i> Crear cuenta
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <main>
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
                            Melgarejo 669, Valparaiso, Chile
                        </li>
                        <li class="mb-2">
                            <i class="bi bi-globe me-2"></i>
                            <a href="https://www.gorevalparaiso.cl" target="_blank" rel="noopener">
                                gorevalparaiso.cl
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
