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
                                                <div class="d-inline-flex gap-1 justify-content-end">
                                                    {{-- Mover arriba --}}
                                                    @if ($idx > 0)
                                                        <form method="POST"
                                                              action="{{ route('admin.consultations.stages.move', [$consultation, $stage, 'up']) }}">
                                                            @csrf
                                                            <button class="btn btn-sm btn-outline-secondary" title="Subir">
                                                                <i class="bi bi-arrow-up"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    {{-- Mover abajo --}}
                                                    @if ($idx < $consultation->stages->count() - 1)
                                                        <form method="POST"
                                                              action="{{ route('admin.consultations.stages.move', [$consultation, $stage, 'down']) }}">
                                                            @csrf
                                                            <button class="btn btn-sm btn-outline-secondary" title="Bajar">
                                                                <i class="bi bi-arrow-down"></i>
                                                            </button>
                                                        </form>
                                                    @endif
                                                    {{-- Editar --}}
                                                    <a href="{{ route('admin.consultations.stages.edit', [$consultation, $stage]) }}"
                                                       class="btn btn-sm btn-outline-secondary" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    {{-- Eliminar --}}
                                                    <form method="POST"
                                                          action="{{ route('admin.consultations.stages.destroy', [$consultation, $stage]) }}"
                                                          onsubmit="return confirm('Eliminar la etapa &quot;{{ $stage->name }}&quot;? Las observaciones asociadas no se pueden eliminar.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" title="Eliminar">
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

                {{-- ========== ANTECEDENTES TECNICOS ========== --}}
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h2 class="h6 mb-0 d-flex align-items-center">
                            Antecedentes tecnicos
                            <span class="gore-badge gore-badge-muted ms-2">
                                {{ $consultation->documents->count() }}
                            </span>
                        </h2>
                        <button type="button" class="btn btn-sm btn-primary"
                                data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Subir documento
                        </button>
                    </div>

                    @if ($consultation->documents->isEmpty())
                        <div class="card-body text-center py-5">
                            <i class="bi bi-file-earmark-arrow-up display-6 text-muted d-block mb-2"></i>
                            <p class="text-muted small mb-3">Aun no hay antecedentes subidos.</p>
                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal" data-bs-target="#uploadDocumentModal">
                                Subir el primer documento
                            </button>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="small text-uppercase" style="color: var(--gore-ink-soft);">
                                    <tr>
                                        <th>Titulo</th>
                                        <th>Etapa</th>
                                        <th class="text-end">Tamano</th>
                                        <th class="text-center">Version</th>
                                        <th>Subido por</th>
                                        <th class="text-end">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($consultation->documents as $doc)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-file-earmark-text me-2" style="color: var(--gore-primary);"></i>
                                                    <div>
                                                        <div class="fw-semibold">{{ $doc->title }}</div>
                                                        <div class="small text-muted text-truncate" style="max-width: 28ch;">
                                                            {{ $doc->original_filename }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small">
                                                @if ($doc->stage_id)
                                                    <span class="gore-badge gore-badge-brand">
                                                        {{ $consultation->stages->firstWhere('id', $doc->stage_id)?->name ?? 'Etapa' }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small fst-italic">Proceso</span>
                                                @endif
                                            </td>
                                            <td class="text-end small text-muted">
                                                @php
                                                    $bytes = $doc->size_bytes;
                                                    $size = $bytes >= 1048576
                                                        ? round($bytes / 1048576, 1) . ' MB'
                                                        : ($bytes >= 1024 ? round($bytes / 1024, 1) . ' KB' : $bytes . ' B');
                                                @endphp
                                                {{ $size }}
                                            </td>
                                            <td class="text-center">
                                                <span class="gore-badge gore-badge-muted">v{{ $doc->version }}</span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ $doc->uploader?->name ?? '-' }}
                                                <div class="small">{{ $doc->created_at->format('d/m/Y') }}</div>
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex gap-1 justify-content-end">
                                                    <a href="{{ route('admin.consultations.documents.download', [$consultation, $doc]) }}"
                                                       class="btn btn-sm btn-outline-secondary" title="Descargar">
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary"
                                                            title="Reemplazar (nueva version)"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#replaceDocumentModal"
                                                            data-action="{{ route('admin.consultations.documents.replace', [$consultation, $doc]) }}"
                                                            data-doc-title="{{ $doc->title }}"
                                                            data-doc-version="{{ $doc->version }}">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                    <form method="POST"
                                                          action="{{ route('admin.consultations.documents.destroy', [$consultation, $doc]) }}"
                                                          onsubmit="return confirm('Archivar el documento &quot;{{ $doc->title }}&quot;? El archivo se conserva pero deja de listarse.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger" title="Archivar">
                                                            <i class="bi bi-archive"></i>
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

    {{-- ========== MODAL: Subir documento ========== --}}
    <div class="modal fade" id="uploadDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST"
                      action="{{ route('admin.consultations.documents.store', $consultation) }}"
                      enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Subir antecedente tecnico</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <x-input-label for="document_file" value="Archivo *" />
                            <input id="document_file" name="file" type="file" required
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.zip,.mp4,.xlsx,.docx">
                            <div class="form-text">
                                PDF, JPG, PNG, DWG, DXF, ZIP, MP4, XLSX, DOCX. Maximo 100 MB.
                            </div>
                            <x-input-error :messages="$errors->get('file')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="document_title" value="Titulo *" />
                            <x-text-input id="document_title" name="title" type="text"
                                          placeholder="Ej: Memoria explicativa, Plano regulador, Estudio impacto"
                                          required maxlength="255" />
                            <x-input-error :messages="$errors->get('title')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="document_stage" value="Asociar a etapa (opcional)" />
                            <select id="document_stage" name="stage_id" class="form-select">
                                <option value="">Documento a nivel del proceso (todas las etapas)</option>
                                @foreach ($consultation->stages as $stage)
                                    <option value="{{ $stage->id }}">
                                        {{ $stage->position }}. {{ $stage->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('stage_id')" />
                        </div>

                        <div class="mb-3">
                            <x-input-label for="document_description" value="Descripcion (opcional)" />
                            <textarea id="document_description" name="description" rows="2" maxlength="1000"
                                      class="form-control"></textarea>
                            <x-input-error :messages="$errors->get('description')" />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Subir
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ========== MODAL: Reemplazar (nueva version) ========== --}}
    <div class="modal fade" id="replaceDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" data-replace-form>
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Reemplazar documento
                            <span class="small text-muted ms-2" data-replace-title></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            La version actual (<span data-replace-version></span>) se archivara automaticamente.
                            La nueva sera <strong data-replace-next></strong>. Ambas se conservan en disco para auditoria.
                        </div>

                        <div class="mb-3">
                            <x-input-label for="replace_file" value="Nuevo archivo *" />
                            <input id="replace_file" name="file" type="file" required
                                   class="form-control"
                                   accept=".pdf,.jpg,.jpeg,.png,.dwg,.dxf,.zip,.mp4,.xlsx,.docx">
                            <div class="form-text">Maximo 100 MB.</div>
                        </div>

                        {{-- title es required en StoreConsultationDocumentRequest,
                             pero en replace conservamos el titulo del documento original.
                             Lo enviamos como hidden poblado por JS para que pase validacion. --}}
                        <input type="hidden" name="title" data-replace-title-input>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-arrow-repeat me-1"></i> Subir nueva version
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('replaceDocumentModal')?.addEventListener('show.bs.modal', function (event) {
            const trigger = event.relatedTarget;
            const action = trigger.getAttribute('data-action');
            const title = trigger.getAttribute('data-doc-title');
            const version = parseInt(trigger.getAttribute('data-doc-version'), 10);
            const form = this.querySelector('[data-replace-form]');
            form.action = action;
            this.querySelector('[data-replace-title]').textContent = title;
            this.querySelector('[data-replace-version]').textContent = 'v' + version;
            this.querySelector('[data-replace-next]').textContent = 'v' + (version + 1);
            this.querySelector('[data-replace-title-input]').value = title;
        });
    </script>
</x-app-layout>
