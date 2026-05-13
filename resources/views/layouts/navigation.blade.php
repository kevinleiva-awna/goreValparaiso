<nav class="navbar navbar-expand-lg gore-navbar">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">
            <x-application-logo />
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav"
                aria-controls="adminNav" aria-expanded="false" aria-label="Abrir navegacion">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav me-auto ms-lg-4 mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}"
                       class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('admin.consultations.index') }}"
                       class="nav-link {{ request()->routeIs('admin.consultations.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text me-1"></i> Consultas
                    </a>
                </li>
                @if (Auth::user()->isSuperAdmin())
                    <li class="nav-item">
                        <a href="{{ route('admin.users.index') }}"
                           class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <i class="bi bi-people me-1"></i> Usuarios
                        </a>
                    </li>
                @endif
            </ul>

            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#"
                       data-bs-toggle="dropdown" aria-expanded="false" style="gap: 0.5rem;">
                        <span class="d-inline-flex align-items-center justify-content-center"
                              style="width: 32px; height: 32px; background: var(--gore-primary); color: #fff; border-radius: 50%; font-size: 0.875rem; font-weight: 600;">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </span>
                        <span class="d-none d-md-inline">
                            <span class="d-block lh-1" style="font-size: 0.875rem;">
                                {{ Auth::user()->name }} {{ Auth::user()->last_name }}
                            </span>
                            <span class="d-block lh-1 small" style="color: var(--gore-ink-soft); font-size: 0.75rem;">
                                {{ ucfirst(Auth::user()->role) }}
                            </span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg" style="min-width: 240px;">
                        <li>
                            <div class="dropdown-item-text px-3 py-2">
                                <div class="fw-semibold text-truncate" style="color: var(--gore-ink);">
                                    {{ Auth::user()->name }} {{ Auth::user()->last_name }}
                                </div>
                                <div class="small text-truncate" style="color: var(--gore-ink-soft);">
                                    {{ Auth::user()->email }}
                                </div>
                                <span class="gore-badge gore-badge-brand mt-2">{{ Auth::user()->role }}</span>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                <i class="bi bi-gear me-2"></i>Mi perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="{{ url('/') }}" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Ver sitio publico
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
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
