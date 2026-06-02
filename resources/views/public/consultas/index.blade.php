<x-public-layout>
    @section('title', 'Consultas publicas')

    {{-- Encabezado --}}
    <section class="bg-white border-bottom" style="border-color: var(--gore-border) !important;">
        <div class="container py-5">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <span class="gore-badge gore-badge-brand mb-2">Portal Ciudadano</span>
                    <h1 class="display-5 fw-bold mb-2" style="letter-spacing: -0.02em;">
                        Consultas publicas
                    </h1>
                    <p class="lead text-muted mb-0">
                        Procesos de participacion ciudadana abiertos por el Gobierno Regional
                        de Valparaiso sobre Instrumentos de Planificacion y Ordenamiento Territorial.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="d-inline-flex flex-column align-items-end small text-muted">
                        <span><i class="bi bi-shield-check me-1"></i> Identidad verificada</span>
                        <span><i class="bi bi-clock-history me-1"></i> Trazabilidad inalterable</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Filtros --}}
    <section class="py-4" style="background-color: var(--gore-bg);">
        <div class="container">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small text-muted mb-1">Buscar</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               class="form-control border-start-0 ps-0"
                               placeholder="Titulo o palabra clave...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted mb-1">Tipo de instrumento</label>
                    <select name="type" class="form-select">
                        <option value="">Todos los tipos</option>
                        @foreach (['IPT' => 'IPT — Instrumento Planificacion', 'PROT' => 'PROT — Plan Regional', 'ZUBC' => 'ZUBC — Borde Costero', 'OTRO' => 'Otro'] as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-muted mb-1">Estado</label>
                    <select name="status" class="form-select">
                        <option value="">Todos</option>
                        <option value="active" @selected(($filters['status'] ?? '') === 'active')>Activas</option>
                        <option value="published" @selected(($filters['status'] ?? '') === 'published')>Por iniciar</option>
                        <option value="closed" @selected(($filters['status'] ?? '') === 'closed')>Cerradas</option>
                    </select>
                </div>
                <div class="col-md-2 d-grid">
                    <button class="btn btn-primary">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </section>

    {{-- Listado --}}
    <section class="py-5">
        <div class="container">
            @if ($consultations->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-5 text-muted d-block mb-3"></i>
                    <h2 class="h5">No hay consultas que coincidan con los filtros</h2>
                    <p class="text-muted">Prueba quitar algun filtro o vuelve mas tarde.</p>
                    <a href="{{ route('public.consultations.index') }}" class="btn btn-outline-primary btn-sm">
                        Limpiar filtros
                    </a>
                </div>
            @else
                <div class="row g-4 mb-4">
                    @foreach ($consultations as $c)
                        @php
                            $daysLeft = $c->ends_at ? now()->diffInDays($c->ends_at, false) : null;
                            $isOpen = $c->status === 'active';
                            $isClosed = $c->status === 'closed';
                            $statusClass = match($c->status) {
                                'active' => 'gore-badge-success',
                                'published' => 'gore-badge-info',
                                'closed' => 'gore-badge-muted',
                                default => 'gore-badge-muted',
                            };
                            $statusLabel = match($c->status) {
                                'active' => 'Activa',
                                'published' => 'Por iniciar',
                                'closed' => 'Cerrada',
                                default => $c->status,
                            };
                            // Urgencia (acta junio 2026, punto 1):
                            // >7 dias verde, 3-7 ambar, <3 rojo. Solo para 'active'.
                            $urgencyColor = match (true) {
                                ! $isOpen || $daysLeft === null => null,
                                $daysLeft < 3 => '#dc2626',
                                $daysLeft <= 7 => '#d97706',
                                default => '#059669',
                            };
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('public.consultations.show', $c->slug) }}"
                               class="gore-consultation-card">
                                <div class="gore-consultation-meta">
                                    <span class="gore-badge gore-badge-brand">{{ $c->instrument_type }}</span>
                                    <span class="gore-badge {{ $statusClass }}">
                                        @if ($isOpen)
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                        @endif
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                                <h3 class="gore-consultation-title">{{ $c->title }}</h3>
                                @if ($c->summary)
                                    <p class="mb-0 small text-muted text-truncate" style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; white-space: normal; overflow: hidden;">
                                        {{ $c->summary }}
                                    </p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-auto gap-2 flex-wrap"
                                     style="border-top: 1px solid var(--gore-border);">
                                    <span class="small d-flex align-items-center gap-2 flex-wrap"
                                          style="color: var(--gore-ink-soft);">
                                        @if ($isOpen && $daysLeft !== null && $daysLeft > 0)
                                            <span class="d-inline-flex align-items-center"
                                                  style="@if($urgencyColor) color: {{ $urgencyColor }}; font-weight: 600;@endif">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ floor($daysLeft) }} {{ floor($daysLeft) === 1 ? 'dia' : 'dias' }} restantes
                                            </span>
                                        @elseif ($isClosed)
                                            <span>
                                                <i class="bi bi-clock-history me-1"></i>
                                                Proceso cerrado
                                            </span>
                                        @elseif ($c->starts_at)
                                            <span>
                                                <i class="bi bi-calendar-event me-1"></i>
                                                Inicia el {{ $c->starts_at->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if ($c->observations_count > 0)
                                            <span class="text-muted">
                                                &middot;
                                                <i class="bi bi-chat-left-text me-1"></i>
                                                {{ number_format($c->observations_count, 0, ',', '.') }} obs.
                                            </span>
                                        @endif
                                    </span>
                                    <span class="small fw-semibold" style="color: var(--gore-primary);">
                                        Ver <i class="bi bi-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>

                @if ($consultations->hasPages())
                    <div class="d-flex justify-content-center">
                        {{ $consultations->links() }}
                    </div>
                @endif

                <p class="text-center text-muted small mt-4 mb-0">
                    Mostrando {{ $consultations->firstItem() }} - {{ $consultations->lastItem() }}
                    de {{ $consultations->total() }} consultas
                </p>
            @endif
        </div>
    </section>
</x-public-layout>
