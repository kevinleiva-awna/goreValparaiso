<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0">Observacion ciudadana</h1>
                <code class="small text-muted">{{ $observation->public_id }}</code>
            </div>
            <a href="{{ route('admin.observations.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="row g-4">
            {{-- Cuerpo de la observacion --}}
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-4 p-md-5">
                        @if ($observation->subject)
                            <div class="small text-muted text-uppercase mb-1" style="letter-spacing: 0.05em;">
                                Asunto
                            </div>
                            <h2 class="h4 fw-bold mb-3">{{ $observation->subject }}</h2>
                        @endif

                        @if ($observation->category)
                            <span class="gore-badge gore-badge-brand mb-3">{{ $observation->category }}</span>
                        @endif

                        <div class="small text-muted text-uppercase mb-2" style="letter-spacing: 0.05em;">
                            Cuerpo de la observacion
                        </div>
                        <div class="text-prewrap p-3 rounded"
                             style="background: var(--gore-bg); line-height: 1.7;">{{ $observation->body }}</div>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @include('admin.observations._response_card', ['observation' => $observation])
            </div>

            {{-- Sidebar: trazabilidad --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 mb-0">
                            <i class="bi bi-person-check me-2" style="color: var(--gore-primary);"></i>
                            Identidad (snapshot inalterable)
                        </h2>
                    </div>
                    <div class="card-body small">
                        <dl class="mb-0">
                            <dt class="text-muted">Nombre completo</dt>
                            <dd class="mb-2 fw-semibold">{{ $observation->snapshot_full_name }}</dd>

                            <dt class="text-muted">RUT</dt>
                            <dd class="mb-2">
                                {{ $observation->snapshot_national_id ?? '— (no aplica)' }}
                            </dd>

                            <dt class="text-muted">Correo electronico</dt>
                            <dd class="mb-2 text-break">{{ $observation->snapshot_email }}</dd>

                            <dt class="text-muted">Metodo de identificacion</dt>
                            <dd class="mb-0">
                                @if ($observation->auth_method_used === 'claveunica')
                                    <span class="gore-badge gore-badge-brand">
                                        <i class="bi bi-shield-check me-1" style="font-size: 0.6rem;"></i>
                                        ClaveUnica
                                    </span>
                                @elseif ($observation->auth_method_used === 'guest')
                                    <span class="gore-badge gore-badge-muted">
                                        <i class="bi bi-person me-1" style="font-size: 0.6rem;"></i>
                                        Sin registro
                                    </span>
                                @else
                                    <span class="gore-badge gore-badge-info">Registro manual</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                @if ($observation->hasAttachment())
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-0">
                                <i class="bi bi-paperclip me-2" style="color: var(--gore-primary);"></i>
                                Archivo adjunto
                            </h2>
                        </div>
                        <div class="card-body small">
                            <div class="text-truncate mb-2 fw-semibold" title="{{ $observation->attachment_original_name }}">
                                {{ $observation->attachment_original_name }}
                            </div>
                            <div class="text-muted mb-3">
                                {{ $observation->attachment_mime_type }} ·
                                {{ number_format(($observation->attachment_size_bytes ?? 0) / 1024, 0, ',', '.') }} KB
                            </div>
                            <a href="{{ route('admin.observations.attachment.download', $observation) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Descargar
                            </a>
                        </div>
                    </div>
                @endif

                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom py-3">
                        <h2 class="h6 mb-0">
                            <i class="bi bi-clock-history me-2" style="color: var(--gore-primary);"></i>
                            Trazabilidad
                        </h2>
                    </div>
                    <div class="card-body small">
                        <dl class="mb-0">
                            <dt class="text-muted">Proceso</dt>
                            <dd class="mb-2">
                                <a href="{{ route('admin.consultations.show', $observation->consultation) }}"
                                   class="text-decoration-none">
                                    {{ $observation->consultation?->title }}
                                </a>
                            </dd>

                            <dt class="text-muted">Etapa</dt>
                            <dd class="mb-2">{{ $observation->stage?->name }}</dd>

                            <dt class="text-muted">Fecha de envio</dt>
                            <dd class="mb-2">{{ $observation->submitted_at->format('d/m/Y H:i:s') }} CLT</dd>

                            <dt class="text-muted">IP de origen</dt>
                            <dd class="mb-2"><code>{{ $observation->ip_address ?? '-' }}</code></dd>

                            <dt class="text-muted">User agent</dt>
                            <dd class="mb-0 text-truncate" style="font-size: 0.75rem;">
                                {{ $observation->user_agent ?? '-' }}
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
