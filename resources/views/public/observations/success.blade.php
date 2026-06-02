<x-public-layout>
    @section('title', 'Observacion registrada')

    <section class="py-5">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-md-9 col-lg-7">

                    <div class="text-center mb-4">
                        <div class="d-inline-flex align-items-center justify-content-center mb-3"
                             style="width: 88px; height: 88px;
                                    background: rgba(16,185,129,0.12); color: var(--gore-success);
                                    border-radius: 50%;">
                            <i class="bi bi-check-lg" style="font-size: 3rem;"></i>
                        </div>
                        <h1 class="h2 fw-bold mb-2" style="letter-spacing: -0.02em;">
                            Tu observacion quedo registrada
                        </h1>
                        <p class="text-muted mb-0">
                            Gracias por participar en el proceso de consulta publica del Gobierno Regional.
                        </p>
                    </div>

                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Codigo de seguimiento</div>
                                    <code style="font-size: 0.875rem; color: var(--gore-primary);">
                                        {{ $observation->public_id }}
                                    </code>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Fecha de envio</div>
                                    <div class="fw-semibold">
                                        {{ $observation->submitted_at->format('d/m/Y H:i') }} hrs (CLT)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Proceso</div>
                                    <div class="fw-semibold">{{ $consultation->title }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Metodo de identificacion</div>
                                    <div class="fw-semibold">
                                        @if ($observation->auth_method_used === 'claveunica')
                                            <i class="bi bi-shield-check me-1" style="color: var(--gore-primary);"></i>
                                            ClaveUnica
                                        @else
                                            <i class="bi bi-person me-1" style="color: var(--gore-primary);"></i>
                                            Sin registro
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <hr>

                            @if ($observation->subject)
                                <div class="mb-3">
                                    <div class="small text-muted mb-1">Asunto</div>
                                    <div class="fw-semibold">{{ $observation->subject }}</div>
                                </div>
                            @endif

                            @if ($observation->category)
                                <div class="mb-3">
                                    <div class="small text-muted mb-1">Categoria</div>
                                    <span class="gore-badge gore-badge-brand">{{ $observation->category }}</span>
                                </div>
                            @endif

                            <div class="mb-0">
                                <div class="small text-muted mb-2">Tu observacion</div>
                                <div class="p-3 rounded text-prewrap"
                                     style="background: var(--gore-bg); color: var(--gore-ink); line-height: 1.6;">{{ $observation->body }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex small">
                        <i class="bi bi-envelope me-2 flex-shrink-0" style="font-size: 1.1rem;"></i>
                        <div>
                            Te enviamos una copia de tu observacion al correo
                            <strong>{{ $observation->snapshot_email }}</strong>.
                            Tu observacion sera revisada por la Unidad de Ordenamiento Territorial.
                            Si recibe respuesta institucional, te llegara al mismo correo.
                        </div>
                    </div>

                    <div class="d-flex flex-column flex-md-row gap-3 justify-content-center mt-4">
                        <a href="{{ route('public.consultations.show', $consultation->slug) }}"
                           class="btn btn-outline-primary">
                            <i class="bi bi-arrow-left me-1"></i> Volver al proceso
                        </a>
                        <a href="{{ route('public.consultations.index') }}" class="btn btn-primary">
                            Ver otras consultas
                            <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
