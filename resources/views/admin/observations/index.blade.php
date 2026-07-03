<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1 class="h3 mb-0">Observaciones recibidas</h1>
                <p class="text-muted small mb-0">
                    Listado completo con identidad verificada y trazabilidad inalterable.
                </p>
            </div>
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="bi bi-download me-1"></i> Exportar
                </button>
                {{-- El JS (public/js/admin-observations.js) reescribe la URL al
                     click leyendo el estado ACTUAL del form de filtros, para que
                     un filtro cambiado sin "Filtrar" igual se respete. El href es
                     el fallback server-side (filtros con los que se cargo la
                     pagina) por si el JS no corre. --}}
                @php
                    $exportFilters = array_filter($filters ?? [], fn ($v) => $v !== null && $v !== '');
                @endphp
                <ul class="dropdown-menu dropdown-menu-end shadow-lg">
                    <li>
                        <a class="dropdown-item"
                           href="{{ route('admin.observations.export', array_merge(['format' => 'xlsx'], $exportFilters)) }}"
                           data-export-format="xlsx"
                           data-export-base="{{ route('admin.observations.export', ['format' => 'xlsx']) }}">
                            <i class="bi bi-file-earmark-excel me-2 text-success"></i>
                            Excel (.xlsx)
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item"
                           href="{{ route('admin.observations.export', array_merge(['format' => 'csv'], $exportFilters)) }}"
                           data-export-format="csv"
                           data-export-base="{{ route('admin.observations.export', ['format' => 'csv']) }}">
                            <i class="bi bi-filetype-csv me-2 text-info"></i>
                            CSV (.csv)
                        </a>
                    </li>
                </ul>
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

        <div class="mb-3 d-flex gap-2">
            <a href="{{ route('admin.observations.index') }}"
               class="btn btn-sm {{ $showArchived ? 'btn-outline-secondary' : 'btn-primary' }}">
                Recibidas
            </a>
            <a href="{{ route('admin.observations.index', ['archived' => 1]) }}"
               class="btn btn-sm {{ $showArchived ? 'btn-primary' : 'btn-outline-secondary' }}">
                <i class="bi bi-archive me-1"></i> Archivadas
            </a>
        </div>

        <div class="card border-0 shadow-sm mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end" id="observations-filter-form">
                    @if ($showArchived)<input type="hidden" name="archived" value="1">@endif
                    <div class="col-md-4">
                        <label class="form-label small text-muted mb-1">Busqueda</label>
                        <input type="text" name="q" value="{{ $filters['q'] ?? '' }}"
                               class="form-control"
                               placeholder="Texto, RUT, nombre, correo o codigo UUID">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted mb-1">Proceso</label>
                        <select name="consultation_id" class="form-select">
                            <option value="">Todos los procesos</option>
                            @foreach ($consultations as $c)
                                <option value="{{ $c->id }}"
                                        @selected((int) ($filters['consultation_id'] ?? 0) === $c->id)>
                                    {{ Str::limit($c->title, 50) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted mb-1">Metodo auth</label>
                        <select name="auth_method" class="form-select">
                            <option value="">Todos</option>
                            <option value="claveunica" @selected(($filters['auth_method'] ?? '') === 'claveunica')>ClaveUnica</option>
                            <option value="guest" @selected(($filters['auth_method'] ?? '') === 'guest')>Sin registro</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted mb-1">Desde</label>
                        <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label small text-muted mb-1">Hasta</label>
                        <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control">
                    </div>
                    <div class="col-md-1 d-grid">
                        <button class="btn btn-outline-primary" title="Filtrar">
                            <i class="bi bi-funnel"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <form method="GET" action="{{ route('admin.observations.batch.create') }}" id="batch-form">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="small text-uppercase" style="color: var(--gore-ink-soft);">
                            <tr>
                                <th style="width: 36px;">
                                    <input type="checkbox" class="form-check-input" id="select-all"
                                           title="Seleccionar todas las visibles">
                                </th>
                                <th>Fecha</th>
                                <th>Ciudadano</th>
                                <th>Proceso</th>
                                <th>Asunto</th>
                                <th class="text-center">Auth</th>
                                <th class="text-center">Respuesta</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($observations as $obs)
                                @php
                                    $hasResponse = $obs->response !== null;
                                    $responsePublished = $hasResponse && $obs->response->status === 'published';
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox"
                                               class="form-check-input row-check"
                                               name="observation_ids[]"
                                               value="{{ $obs->id }}"
                                               @disabled($hasResponse)
                                               @if ($hasResponse) title="Esta observacion ya tiene respuesta" @endif>
                                    </td>
                                    <td class="small text-nowrap">
                                        <div>{{ $obs->submitted_at->format('d/m/Y') }}</div>
                                        <div class="text-muted">{{ $obs->submitted_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">{{ $obs->display_name }}</div>
                                        @if ($obs->snapshot_actor_type && $obs->snapshot_actor_type !== \App\Models\Observation::ACTOR_NATURAL)
                                            <div class="text-muted" style="font-size: 0.7rem;">
                                                {{ $obs->actor_type_label }}@if ($obs->snapshot_trade_name) &middot; {{ $obs->snapshot_trade_name }}@endif
                                            </div>
                                        @endif
                                        <div class="small text-muted">
                                            {{ $obs->display_rut }} &middot; {{ $obs->snapshot_email }}
                                        </div>
                                    </td>
                                    <td class="small">
                                        <div>{{ Str::limit($obs->consultation?->title, 40) }}</div>
                                    </td>
                                    <td class="small">
                                        @if ($obs->subject)
                                            <div class="fw-semibold">{{ Str::limit($obs->subject, 50) }}</div>
                                        @endif
                                        <div class="text-muted">{{ Str::limit($obs->body, 80) }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if ($obs->auth_method_used === 'claveunica')
                                            <span class="gore-badge gore-badge-brand">
                                                <i class="bi bi-shield-check me-1" style="font-size: 0.6rem;"></i>CU
                                            </span>
                                        @elseif ($obs->auth_method_used === 'guest')
                                            <span class="gore-badge gore-badge-muted">Guest</span>
                                        @else
                                            <span class="gore-badge gore-badge-info">Manual</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($responsePublished)
                                            <span class="gore-badge gore-badge-success">
                                                <i class="bi bi-check2-circle me-1" style="font-size: 0.6rem;"></i>
                                                Publicada
                                            </span>
                                        @elseif ($hasResponse)
                                            <span class="gore-badge gore-badge-info">Borrador</span>
                                        @else
                                            <span class="gore-badge gore-badge-muted">Sin respuesta</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-nowrap">
                                        @if ($showArchived)
                                            @if (auth()->user()->isSuperAdmin())
                                                <button type="submit" form="restore-{{ $obs->id }}"
                                                        class="btn btn-sm btn-outline-success">
                                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                                                </button>
                                            @else
                                                <span class="gore-badge gore-badge-muted">Archivada</span>
                                            @endif
                                        @else
                                            <a href="{{ route('admin.observations.show', $obs) }}"
                                               class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-eye me-1"></i> Ver
                                            </a>
                                            @if (auth()->user()->isSuperAdmin())
                                                <button type="submit" form="archive-{{ $obs->id }}"
                                                        class="btn btn-sm btn-outline-danger" title="Archivar">
                                                    <i class="bi bi-archive"></i>
                                                </button>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox display-6 d-block mb-2"></i>
                                        No hay observaciones que coincidan con los filtros.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($observations->hasPages())
                    <div class="card-footer bg-white border-top-0">
                        {{ $observations->links() }}
                    </div>
                @endif
            </div>

            {{-- Barra de acciones masivas: aparece cuando hay 1+ checkbox marcado --}}
            <div id="bulk-bar"
                 class="position-fixed bottom-0 start-50 translate-middle-x mb-3 d-none"
                 style="z-index: 1050;">
                <div class="card shadow-lg border-0">
                    <div class="card-body d-flex align-items-center gap-3 py-2 px-3">
                        <span class="small">
                            <strong id="bulk-count">0</strong> observacion(es) seleccionada(s)
                        </span>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bi bi-reply-all me-1"></i> Responder en lote
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-clear">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </form>

        {{-- Forms de archivar/restaurar FUERA del batch-form (no se pueden
             anidar): los botones de cada fila los disparan via el atributo
             HTML5 form="...". Solo super-admin. --}}
        @if (auth()->user()->isSuperAdmin())
            @foreach ($observations as $obs)
                @if ($showArchived)
                    <form id="restore-{{ $obs->id }}" method="POST"
                          action="{{ route('admin.observations.restore', $obs) }}"
                          data-confirm="Restaurar esta observacion? Volvera al listado.">
                        @csrf
                        @method('PUT')
                    </form>
                @else
                    <form id="archive-{{ $obs->id }}" method="POST"
                          action="{{ route('admin.observations.archive', $obs) }}"
                          data-confirm="Archivar esta observacion? Sale del listado y del export, pero podras restaurarla.">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach
        @endif

        {{-- OJO: nada de <script> inline aqui — la CSP (script-src 'self') lo
             bloquea en prod/staging y el JS muere en silencio (asi se rompio
             el export y la seleccion masiva). Todo el JS de esta pagina vive
             en public/js/admin-observations.js. --}}
        <script src="{{ asset('js/admin-observations.js') }}" defer></script>

        <p class="text-center text-muted small mt-3 mb-0">
            Mostrando {{ $observations->firstItem() ?? 0 }} - {{ $observations->lastItem() ?? 0 }}
            de {{ $observations->total() }} observaciones
        </p>
    </div>
</x-app-layout>
