<x-public-layout>
    @section('title', 'Observaciones registradas')

    @php
        $first = $observations->first();
        $count = $observations->count();
    @endphp

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
                            @if ($count === 1)
                                Tu observacion quedo registrada
                            @else
                                Tus {{ $count }} observaciones quedaron registradas
                            @endif
                        </h1>
                        <p class="text-muted mb-0">
                            Gracias por participar en el proceso de consulta publica del Gobierno Regional.
                        </p>
                    </div>

                    {{-- Resumen del envio: datos compartidos por todas las observaciones --}}
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4 p-md-5">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Fecha de envio</div>
                                    <div class="fw-semibold">
                                        {{ $first->submitted_at->format('d/m/Y H:i') }} hrs (CLT)
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Proceso</div>
                                    <div class="fw-semibold">{{ $consultation->title }}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Metodo de identificacion</div>
                                    <div class="fw-semibold">
                                        @if ($first->auth_method_used === 'claveunica')
                                            <i class="bi bi-shield-check me-1" style="color: var(--gore-primary);"></i>
                                            ClaveUnica
                                        @else
                                            <i class="bi bi-person me-1" style="color: var(--gore-primary);"></i>
                                            Sin registro
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="small text-muted mb-1">Observaciones</div>
                                    <div class="fw-semibold">{{ $count }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Detalle de cada observacion del envio --}}
                    @foreach ($observations as $obs)
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <span class="fw-semibold">Observacion {{ $loop->iteration }}</span>
                                        @if ($obs->category)
                                            <span class="gore-badge gore-badge-brand">{{ $obs->category }}</span>
                                        @endif
                                    </div>
                                    <code class="small" style="color: var(--gore-primary);">{{ $obs->public_id }}</code>
                                </div>

                                @if ($obs->subject)
                                    <div class="mb-2">
                                        <div class="small text-muted mb-1">Asunto</div>
                                        <div class="fw-semibold">{{ $obs->subject }}</div>
                                    </div>
                                @endif

                                <div class="p-3 rounded text-prewrap"
                                     style="background: var(--gore-bg); color: var(--gore-ink); line-height: 1.6;">{{ $obs->body }}</div>

                                @if ($obs->hasAttachment())
                                    <div class="small text-muted mt-2">
                                        <i class="bi bi-paperclip me-1"></i>{{ $obs->attachment_original_name }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="alert alert-info d-flex small mt-4">
                        <i class="bi bi-envelope me-2 flex-shrink-0" style="font-size: 1.1rem;"></i>
                        <div>
                            Te enviamos una copia al correo
                            <strong>{{ $first->snapshot_email }}</strong>.
                            @if ($count === 1)
                                Tu observacion sera revisada
                            @else
                                Tus observaciones seran revisadas
                            @endif
                            por la Unidad de Ordenamiento Territorial.
                            Si recibe(n) respuesta institucional, te llegara al mismo correo.
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
