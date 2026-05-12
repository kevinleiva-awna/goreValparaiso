<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">{{ $consultation->title }}</h1>
                <div class="small text-muted">
                    <span class="badge bg-secondary">{{ $consultation->instrument_type }}</span>
                    <span class="badge bg-info ms-1">{{ $consultation->status }}</span>
                    <span class="ms-2">/ {{ $consultation->slug }}</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.consultations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Volver
                </a>
                <a href="{{ route('admin.consultations.edit', $consultation) }}" class="btn btn-primary">
                    <i class="bi bi-pencil me-1"></i> Editar
                </a>
            </div>
        </div>
    </x-slot>

    <div class="container py-4">
        @if (session('status'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('status') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Resumen</h2>
                    </div>
                    <div class="card-body">
                        @if ($consultation->summary)
                            <p>{{ $consultation->summary }}</p>
                        @else
                            <p class="text-muted small fst-italic">Sin resumen.</p>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Descripcion</h2>
                    </div>
                    <div class="card-body">
                        @if ($consultation->description)
                            <div class="text-prewrap">{!! nl2br(e($consultation->description)) !!}</div>
                        @else
                            <p class="text-muted small fst-italic">Sin descripcion.</p>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0 d-flex align-items-center">
                            Etapas
                            <span class="gore-badge gore-badge-muted ms-2">
                                {{ $consultation->stages->count() }}
                            </span>
                        </h2>
                        <a href="{{ route('admin.consultations.stages.create', $consultation) }}"
                           class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Agregar etapa
                        </a>
                    </div>

                    @if ($consultation->stages->isEmpty())
                        <div class="card-body text-center py-5">
                            <i class="bi bi-diagram-3 display-6 text-muted d-block mb-2"></i>
                            <p class="text-muted small mb-3">Aun no hay etapas configuradas para este proceso.</p>
                            <a href="{{ route('admin.consultations.stages.create', $consultation) }}"
                               class="btn btn-outline-primary btn-sm">
                                Crear primera etapa
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="small text-uppercase" style="color: var(--gore-ink-soft);">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>Nombre</th>
                                        <th>Periodo</th>
                                        <th class="text-center">Acepta obs.</th>
                                        <th class="text-center">Estado</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consultation->stages as $idx => $stage)
                                        <tr>
                                            <td class="text-muted">{{ $stage->position }}</td>
                                            <td>
                                                <div class="fw-semibold">{{ $stage->name }}</div>
                                                @if ($stage->description)
                                                    <div class="small text-muted text-truncate" style="max-width: 28ch;">
                                                        {{ $stage->description }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="small text-muted">
                                                @if ($stage->starts_at && $stage->ends_at)
                                                    {{ $stage->starts_at->format('d/m/Y') }}<br>
                                                    {{ $stage->ends_at->format('d/m/Y') }}
                                                @else
                                                    <span class="fst-italic">Sin fechas</span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if ($stage->accepts_observations)
                                                    <i class="bi bi-check-circle-fill text-success" title="Si"></i>
                                                @else
                                                    <i class="bi bi-dash-circle text-muted" title="No"></i>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @php
                                                    $cls = match($stage->status) {
                                                        'active' => 'gore-badge-success',
                                                        'closed' => 'gore-badge-warning',
                                                        default  => 'gore-badge-muted',
                                                    };
                                                @endphp
                                                <span class="gore-badge {{ $cls }}">{{ $stage->status }}</span>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm">
                                                    {{-- Mover arriba --}}
                                                    @if ($idx > 0)
                                                        <form method="POST"
                                                              action="{{ route('admin.consultations.stages.move', [$consultation, $stage, 'up']) }}"
                                                              class="d-inline">
                                                            @csrf
                                                            <button class="btn btn-outline-secondary" title="Subir">
                                                                <i class="bi bi-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    {{-- Mover abajo --}}
                                                    @if ($idx < $consultation->stages->count() - 1)
                                                        <form method="POST"
                                                              action="{{ route('admin.consultations.stages.move', [$consultation, $stage, 'down']) }}"
                                                              class="d-inline">
                                                            @csrf
                                                            <button class="btn btn-outline-secondary" title="Bajar">
                                                                <i class="bi bi-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    {{-- Editar --}}
                                                    <a href="{{ route('admin.consultations.stages.edit', [$consultation, $stage]) }}"
                                                       class="btn btn-outline-secondary" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    {{-- Eliminar --}}
                                                    <form method="POST"
                                                          action="{{ route('admin.consultations.stages.destroy', [$consultation, $stage]) }}"
                                                          class="d-inline"
                                                          onsubmit="return confirm('Eliminar la etapa &quot;{{ $stage->name }}&quot;? Las observaciones asociadas no se pueden eliminar.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-outline-danger" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Metadatos</h2>
                    </div>
                    <div class="card-body small">
                        <dl class="mb-0">
                            <dt class="text-muted">UUID publico</dt>
                            <dd class="text-break">{{ $consultation->public_id }}</dd>

                            <dt class="text-muted">Periodo del proceso</dt>
                            <dd>
                                @if ($consultation->starts_at)
                                    {{ $consultation->starts_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted fst-italic">sin definir</span>
                                @endif
                                <br>
                                @if ($consultation->ends_at)
                                    {{ $consultation->ends_at->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted fst-italic">sin definir</span>
                                @endif
                            </dd>

                            <dt class="text-muted">Metodos de autenticacion</dt>
                            <dd>
                                @foreach ((array) $consultation->auth_methods as $method)
                                    <span class="badge bg-info text-dark">{{ $method }}</span>
                                @endforeach
                            </dd>

                            <dt class="text-muted">Creada por</dt>
                            <dd>{{ $consultation->creator?->name ?? '-' }} {{ $consultation->creator?->last_name }}</dd>

                            <dt class="text-muted">Creada el</dt>
                            <dd>{{ $consultation->created_at->format('d/m/Y H:i') }}</dd>
                        </dl>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h2 class="h6 mb-0">Estadisticas</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted small">Documentos</span>
                            <span class="fw-bold">{{ $consultation->documents->count() }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted small">Observaciones</span>
                            <span class="fw-bold">{{ $consultation->observations_count }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
