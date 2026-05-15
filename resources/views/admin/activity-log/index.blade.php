<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-0">Bitacora de auditoria</h1>
            <p class="text-muted small mb-0">
                Registro inmutable de acciones criticas (DS 7/2023). No editable, solo consulta.
            </p>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Recurso</label>
                        <select name="log_name" class="form-select">
                            <option value="">Todos</option>
                            @foreach ($logNames as $name)
                                <option value="{{ $name }}" @selected(($filters['log_name'] ?? '') === $name)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Accion</label>
                        <select name="event" class="form-select">
                            <option value="">Todas</option>
                            <option value="created" @selected(($filters['event'] ?? '') === 'created')>Creacion</option>
                            <option value="updated" @selected(($filters['event'] ?? '') === 'updated')>Edicion</option>
                            <option value="deleted" @selected(($filters['event'] ?? '') === 'deleted')>Eliminacion</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Desde</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Hasta</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
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
                            <th>Fecha</th>
                            <th>Usuario</th>
                            <th>Recurso</th>
                            <th>Accion</th>
                            <th>Cambios</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $log)
                            @php
                                $attrs = $log->properties->get('attributes', []);
                                $old = $log->properties->get('old', []);
                            @endphp
                            <tr>
                                <td class="small text-nowrap">
                                    <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-muted">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="small">
                                    @if ($log->causer)
                                        <div class="fw-semibold">{{ $log->causer->name }} {{ $log->causer->last_name ?? '' }}</div>
                                        <div class="text-muted">{{ $log->causer->email ?? '' }}</div>
                                    @else
                                        <span class="text-muted fst-italic">Sistema</span>
                                    @endif
                                </td>
                                <td class="small">
                                    <div class="fw-semibold">{{ $log->log_name ?? '-' }}</div>
                                    <div class="text-muted">ID #{{ $log->subject_id }}</div>
                                </td>
                                <td>
                                    @if ($log->event === 'created')
                                        <span class="gore-badge gore-badge-success">Creacion</span>
                                    @elseif ($log->event === 'updated')
                                        <span class="gore-badge gore-badge-info">Edicion</span>
                                    @elseif ($log->event === 'deleted')
                                        <span class="gore-badge gore-badge-danger">Eliminacion</span>
                                    @else
                                        <span class="gore-badge gore-badge-muted">{{ $log->event }}</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if (! empty($attrs))
                                        <details>
                                            <summary class="text-muted" style="cursor: pointer;">
                                                {{ count($attrs) }} campo(s)
                                            </summary>
                                            <div class="mt-2" style="font-family: ui-monospace, monospace; font-size: 0.7rem;">
                                                @foreach ($attrs as $key => $value)
                                                    <div class="mb-1">
                                                        <strong>{{ $key }}:</strong>
                                                        @if (isset($old[$key]) && $log->event === 'updated')
                                                            <span class="text-muted text-decoration-line-through">
                                                                {{ Str::limit(is_scalar($old[$key]) ? $old[$key] : json_encode($old[$key]), 50) }}
                                                            </span>
                                                            &rarr;
                                                        @endif
                                                        <span>{{ Str::limit(is_scalar($value) ? $value : json_encode($value), 80) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-muted fst-italic">Sin atributos</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bi bi-clock-history display-6 d-block mb-2"></i>
                                    No hay entradas en la bitacora con los filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages())
                <div class="card-footer bg-white border-top-0">
                    {{ $activities->links() }}
                </div>
            @endif
        </div>

        <p class="text-center text-muted small mt-3 mb-0">
            <i class="bi bi-shield-check me-1"></i>
            Mostrando {{ $activities->firstItem() ?? 0 }} - {{ $activities->lastItem() ?? 0 }}
            de {{ $activities->total() }} entradas (orden cronologico inverso).
        </p>
    </div>
</x-app-layout>
