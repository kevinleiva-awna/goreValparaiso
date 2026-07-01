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
    {{-- Capa de blobs azules con pulse (decorativa, detras de todo).
         Sobre fondo blanco del body crea profundidad sin recargar. --}}
    <div class="gore-blobs" aria-hidden="true">
        <div class="gore-blob gore-blob-1"></div>
        <div class="gore-blob gore-blob-2"></div>
        <div class="gore-blob gore-blob-3"></div>
    </div>

    {{-- Skip link para usuarios de teclado / screen readers. Se hace visible
         al recibir foco (estilo en SCSS via .gore-skip-link:focus). --}}
    <a href="#main-content" class="gore-skip-link visually-hidden-focusable">
        Saltar al contenido principal
    </a>

    {{-- Top-bar institucional gov.cl-style: marca Estado + link al sitio
         oficial. NO es sticky — scrollea con la pagina. --}}
    <div class="gore-topbar" aria-label="Identidad institucional">
        <div class="container">
            <span class="gore-topbar-brand">
                <span class="gore-topbar-flag" aria-hidden="true"></span>
                Gobierno Regional &middot; Regi&oacute;n de Valpara&iacute;so
            </span>
            <ul class="gore-topbar-links">
                <li>
                    <a href="https://www.gobiernovalparaiso.cl" target="_blank" rel="noopener noreferrer">
                        <span>Sitio institucional</span>
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </li>
                <li>
                    <a href="https://www.gobiernovalparaiso.cl/normasGraficas.php" target="_blank" rel="noopener noreferrer">
                        <span>Transparencia</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    {{-- Navbar institucional --}}
    <nav class="navbar navbar-expand-lg gore-navbar" aria-label="Navegacion principal">
        <div class="container">
            <a class="navbar-brand py-0 me-lg-4" href="{{ url('/') }}">
                {{-- Logo institucional oficial (Logo_A.png — banner doble:
                     escudo a color + "Gobierno Regional / Region de Valparaiso"
                     + slogan "#Valparaiso Region de Derechos"). 64px para que
                     el escudo y el slogan se lean con presencia. --}}
                <x-application-logo background="light" :height="64" />
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
                        {{-- Menu de cuenta (avatar). Ciudadano: cerrar sesion.
                             Funcionario: perfil + panel + cerrar sesion. El acceso
                             al backoffice vive aqui como item discreto del menu,
                             no como boton prominente en el navbar. --}}
                        @php $navUser = Auth::user(); @endphp
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex align-items-center gap-2"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="d-inline-flex align-items-center justify-content-center"
                                      style="width: 28px; height: 28px; background: var(--gore-primary); color: #fff; border-radius: 50%; font-size: 0.75rem; font-weight: 600;">
                                    {{ strtoupper(substr($navUser->name, 0, 1)) }}
                                </span>
                                <span class="d-none d-md-inline">{{ $navUser->name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 240px;">
                                <li>
                                    <div class="dropdown-item-text px-3 py-2">
                                        <div class="fw-semibold text-truncate" style="color: var(--gore-ink);">
                                            {{ $navUser->name }} {{ $navUser->last_name }}
                                        </div>
                                        <div class="small text-truncate" style="color: var(--gore-ink-soft);">
                                            {{ $navUser->email }}
                                        </div>
                                    </div>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                @if ($navUser->isStaff())
                                    <li>
                                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                            <i class="bi bi-person me-2"></i>Mi perfil
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('dashboard') }}">
                                            <i class="bi bi-grid-1x2 me-2"></i>Ir al panel
                                        </a>
                                    </li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion
                                            </button>
                                        </form>
                                    </li>
                                @else
                                    <li>
                                        <form method="POST" action="{{ route('citizen.logout') }}" class="m-0">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion
                                            </button>
                                        </form>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @else
                        {{-- Sin login: la entrada por ClaveUnica se muestra solo si la
                             integracion esta habilitada. Mientras no lo este, el ciudadano
                             participa como invitado dentro del formulario de observacion. --}}
                        @if (config('claveunica.enabled'))
                            <a href="{{ route('citizen.claveunica.redirect') }}" class="btn btn-primary btn-sm">
                                <i class="bi bi-shield-check me-1"></i> Ingresar con ClaveUnica
                            </a>
                        @endif
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
