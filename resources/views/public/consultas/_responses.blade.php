{{-- Respuestas institucionales publicadas para esta consulta.
     Recibe $publishedResponses (paginator) y $consultation.
     IMPORTANTE: NO expone RUT ni email del ciudadano. Solo el nombre. --}}

@if ($publishedResponses->total() > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
            <h2 class="h4 mb-0 d-flex align-items-center">
                <i class="bi bi-reply-all me-2" style="color: var(--gore-primary);"></i>
                Respuestas institucionales publicadas
            </h2>
            <span class="gore-badge gore-badge-info ms-auto">
                {{ $publishedResponses->total() }}
            </span>
        </div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                @foreach ($publishedResponses as $obs)
                    <div class="list-group-item p-4">
                        {{-- Identidad publica acotada: nombre y fecha (sin RUT ni email) --}}
                        <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                            <div>
                                <div class="fw-semibold" style="color: var(--gore-ink);">
                                    {{ $obs->snapshot_full_name }}
                                </div>
                                <div class="small text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    Envio observacion el {{ $obs->submitted_at->format('d/m/Y') }}
                                    @if ($obs->stage?->name)
                                        &middot; {{ $obs->stage->name }}
                                    @endif
                                </div>
                            </div>
                            <span class="gore-badge gore-badge-success" style="height: fit-content;">
                                <i class="bi bi-check2-circle me-1" style="font-size: 0.65rem;"></i>
                                Respondida
                            </span>
                        </div>

                        @if ($obs->subject)
                            <div class="small text-muted text-uppercase mb-1" style="letter-spacing: 0.05em;">
                                Observacion
                            </div>
                            <p class="fw-semibold mb-2">{{ $obs->subject }}</p>
                        @endif

                        <div class="text-prewrap small mb-3 ps-3"
                             style="color: var(--gore-ink-soft); border-left: 3px solid var(--gore-border); line-height: 1.65;">{{ Str::limit($obs->body, 400) }}</div>

                        {{-- Respuesta institucional --}}
                        <div class="rounded p-3"
                             style="background: rgba(21,28,104,0.04); border-left: 3px solid var(--gore-primary);">
                            <div class="small text-uppercase mb-2 fw-semibold"
                                 style="color: var(--gore-primary); letter-spacing: 0.05em;">
                                <i class="bi bi-reply me-1"></i>
                                Respuesta del Gobierno Regional
                            </div>
                            <div class="text-prewrap" style="color: var(--gore-ink); line-height: 1.7;">{{ $obs->response->content }}</div>
                            <div class="small text-muted mt-2">
                                Publicada el {{ $obs->response->published_at->format('d/m/Y') }}
                                @if ($obs->response->responder)
                                    por {{ $obs->response->responder->name }} {{ $obs->response->responder->last_name }}
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        @if ($publishedResponses->hasPages())
            <div class="card-footer bg-white border-top">
                {{ $publishedResponses->links() }}
            </div>
        @endif
    </div>
@endif
