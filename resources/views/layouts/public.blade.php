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

                <div class="d-flex gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-grid-1x2 me-1"></i> Ir al backoffice
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-shield-lock me-1"></i> Acceso funcionarios
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

            <div class="gore-footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
                <span>&copy; {{ date('Y') }} Gobierno Regional de Valparaiso. Todos los derechos reservados.</span>
                <span>Desarrollo: <a href="https://awna.cl" target="_blank" rel="noopener">AWNA</a></span>
            </div>
        </div>
    </footer>
</body>
</html>
