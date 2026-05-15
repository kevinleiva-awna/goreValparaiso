<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">Usuarios del sistema</h1>
                <p class="text-muted small mb-0">
                    Funcionarios y super-administradores con acceso al backoffice. Los ciudadanos
                    se registran por su cuenta via ClaveUnica o el flujo manual.
                </p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus me-1"></i> Nuevo funcionario
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                {{ $errors->first() }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Busqueda</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               class="form-control"
                               placeholder="Nombre, correo o RUT...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Rol</label>
                        <select name="role" class="form-select">
                            <option value="">Todos</option>
                            <option value="funcionario" @selected(($filters['role'] ?? '') === 'funcionario')>Funcionario</option>
                            <option value="super-admin" @selected(($filters['role'] ?? '') === 'super-admin')>Super-admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Estado</label>
                        <select name="active" class="form-select">
                            <option value="all" @selected(($filters['active'] ?? 'all') === 'all')>Todos</option>
                            <option value="yes" @selected(($filters['active'] ?? '') === 'yes')>Activos</option>
                            <option value="no" @selected(($filters['active'] ?? '') === 'no')>Desactivados</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-grid">
                        <button class="btn btn-outline-primary">
                            <i class="bi bi-funnel me-1"></i> Filtrar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="small text-uppercase" style="color: var(--gore-ink-soft);">
                        <tr>
                            <th>Usuario</th>
                            <th>RUT</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Ultimo ingreso</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="d-inline-flex align-items-center justify-content-center me-2"
                                              style="width: 36px; height: 36px; background: var(--gore-primary); color: #fff; border-radius: 50%; font-size: 0.875rem; font-weight: 600;">
                                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr($user->last_name ?? '', 0, 1)) }}
                                        </span>
                                        <div>
                                            <div class="fw-semibold">
                                                {{ $user->name }} {{ $user->last_name }}
                                                @if ($user->id === auth()->id())
                                                    <span class="gore-badge gore-badge-brand ms-1">tu</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted">{{ $user->email }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="small">{{ $user->national_id ?? '—' }}</td>
                                <td>
                                    @if ($user->isSuperAdmin())
                                        <span class="gore-badge gore-badge-brand">Super-admin</span>
                                    @else
                                        <span class="gore-badge gore-badge-info">Funcionario</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($user->is_active)
                                        <span class="gore-badge gore-badge-success">
                                            <i class="bi bi-check-circle-fill me-1" style="font-size: 0.6rem;"></i>
                                            Activo
                                        </span>
                                    @else
                                        <span class="gore-badge gore-badge-muted">Desactivado</span>
                                    @endif
                                </td>
                                <td class="small text-muted">
                                    @if ($user->last_login_at)
                                        {{ $user->last_login_at->diffForHumans() }}
                                    @else
                                        <span class="fst-italic">Nunca</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('admin.users.edit', $user) }}"
                                           class="btn btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if ($user->id !== auth()->id())
                                            <form method="POST"
                                                  action="{{ route('admin.users.toggle-active', $user) }}"
                                                  onsubmit="return confirm('{{ $user->is_active ? 'Desactivar' : 'Reactivar' }} la cuenta de {{ $user->name }}?');">
                                                @csrf
                                                @if ($user->is_active)
                                                    <button class="btn btn-outline-warning" title="Desactivar">
                                                        <i class="bi bi-pause-fill"></i>
                                                    </button>
                                                @else
                                                    <button class="btn btn-outline-success" title="Reactivar">
                                                        <i class="bi bi-play-fill"></i>
                                                    </button>
                                                @endif
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-people display-6 d-block mb-2"></i>
                                    No hay usuarios que coincidan con los filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($users->hasPages())
                <div class="card-footer bg-white border-top-0">
                    {{ $users->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
