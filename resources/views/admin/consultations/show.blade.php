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
                        <h2 class="h6 mb-0">
                            Etapas
                            <span class="badge bg-secondary ms-1">{{ $consultation->stages->count() }}</span>
                        </h2>
                        <span class="badge bg-light text-muted">Gestion completa en D10</span>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse ($consultation->stages as $stage)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-light text-dark me-2">{{ $stage->position }}</span>
                                    <strong>{{ $stage->name }}</strong>
                                    @if (! $stage->accepts_observations)
                                        <span class="badge bg-secondary ms-1">solo informativa</span>
                                    @endif
                                    <div class="small text-muted">
                                        @if ($stage->starts_at && $stage->ends_at)
                                            {{ $stage->starts_at->format('d/m/Y') }} - {{ $stage->ends_at->format('d/m/Y') }}
                                        @else
                                            Sin fechas
                                        @endif
                                    </div>
                                </div>
                                <span class="badge bg-{{ $stage->status === 'active' ? 'success' : ($stage->status === 'closed' ? 'warning' : 'secondary') }}">
                                    {{ $stage->status }}
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted small fst-italic">
                                Sin etapas configuradas todavia.
                            </li>
                        @endforelse
                    </ul>
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
