<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Consultas publicas</h1>
            <a href="{{ route('admin.consultations.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i> Nueva consulta
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

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Busqueda</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               class="form-control" placeholder="Titulo o slug...">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Tipo</label>
                        <select name="type" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['IPT', 'PROT', 'ZUBC', 'OTRO'] as $type)
                                <option value="{{ $type }}" @selected(($filters['type'] ?? '') === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Estado</label>
                        <select name="status" class="form-select">
                            <option value="">Todos</option>
                            @foreach (['draft' => 'Borrador', 'published' => 'Publicada', 'active' => 'Activa', 'closed' => 'Cerrada', 'archived' => 'Archivada'] as $value => $label)
                                <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
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
                    <thead class="table-light">
                        <tr>
                            <th>Titulo</th>
                            <th class="text-center">Tipo</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Etapas</th>
                            <th class="text-center">Observaciones</th>
                            <th>Periodo</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($consultations as $consultation)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.consultations.show', $consultation) }}"
                                       class="text-decoration-none fw-semibold">
                                        {{ $consultation->title }}
                                    </a>
                                    <div class="text-muted small">{{ $consultation->slug }}</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">{{ $consultation->instrument_type }}</span>
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusClass = match($consultation->status) {
                                            'draft' => 'bg-secondary',
                                            'published' => 'bg-info',
                                            'active' => 'bg-success',
                                            'closed' => 'bg-warning',
                                            'archived' => 'bg-dark',
                                            default => 'bg-light text-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $consultation->status }}</span>
                                </td>
                                <td class="text-center">{{ $consultation->stages_count }}</td>
                                <td class="text-center">{{ $consultation->observations_count }}</td>
                                <td class="small text-muted">
                                    @if ($consultation->starts_at)
                                        {{ $consultation->starts_at->format('d/m/Y') }}
                                    @endif
                                    @if ($consultation->ends_at)
                                        - {{ $consultation->ends_at->format('d/m/Y') }}
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.consultations.edit', $consultation) }}"
                                       class="btn btn-sm btn-outline-secondary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST"
                                          action="{{ route('admin.consultations.destroy', $consultation) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Archivar esta consulta?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-archive"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                    No hay consultas que coincidan con los filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($consultations->hasPages())
                <div class="card-footer bg-white border-top-0">
                    {{ $consultations->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
