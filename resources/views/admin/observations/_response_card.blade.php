@php
    $response = $observation->response;
    $isPublished = $response && $response->isPublished();
    $isDraft = $response && $response->isDraft();
@endphp

<div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white border-bottom py-3 d-flex align-items-center">
        <h2 class="h6 mb-0 d-flex align-items-center">
            <i class="bi bi-reply me-2" style="color: var(--gore-primary);"></i>
            Respuesta institucional
        </h2>
        <div class="ms-auto">
            @if ($isPublished)
                <span class="gore-badge gore-badge-success">
                    <i class="bi bi-check2-circle me-1" style="font-size: 0.65rem;"></i>
                    Publicada
                </span>
            @elseif ($isDraft)
                <span class="gore-badge gore-badge-info">Borrador</span>
            @else
                <span class="gore-badge gore-badge-muted">Sin respuesta</span>
            @endif
        </div>
    </div>

    <div class="card-body p-4 p-md-5">
        @if ($isPublished)
            {{-- Respuesta publicada: lectura inmutable --}}
            <div class="text-prewrap p-3 rounded mb-3"
                 style="background: var(--gore-bg); line-height: 1.7;">{{ $response->content }}</div>
            <dl class="row small mb-0">
                <dt class="col-sm-4 text-muted">Publicada por</dt>
                <dd class="col-sm-8 fw-semibold">{{ $response->responder?->name }} {{ $response->responder?->last_name }}</dd>

                <dt class="col-sm-4 text-muted">Fecha de publicacion</dt>
                <dd class="col-sm-8">{{ $response->published_at->format('d/m/Y H:i:s') }} CLT</dd>

                @if ($response->batch_id)
                    <dt class="col-sm-4 text-muted">Lote</dt>
                    <dd class="col-sm-8"><code>{{ Str::limit($response->batch_id, 8, '') }}</code></dd>
                @endif
            </dl>
            <p class="small text-muted mt-3 mb-0 fst-italic">
                <i class="bi bi-shield-check me-1"></i>
                Las respuestas publicadas son inmutables. Se notifico al ciudadano por correo a
                <code>{{ $observation->snapshot_email }}</code>.
            </p>
        @elseif ($isDraft)
            {{-- Borrador: editable y publicable --}}
            <form method="POST"
                  action="{{ route('admin.observations.response.update', $observation) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <x-input-label for="response_content" value="Contenido de la respuesta *" />
                    <textarea id="response_content" name="content" class="form-control" rows="6"
                              required minlength="10" maxlength="5000">{{ old('content', $response->content) }}</textarea>
                    <div class="form-text">
                        Minimo 10, maximo 5.000 caracteres. Este texto se notificara por correo al ciudadano al publicarse.
                    </div>
                    <x-input-error :messages="$errors->get('content')" />
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <x-secondary-button type="submit">
                        <i class="bi bi-save me-1"></i> Guardar borrador
                    </x-secondary-button>
                </div>
            </form>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <form method="POST"
                      action="{{ route('admin.observations.response.destroy', $observation) }}"
                      onsubmit="return confirm('Descartar este borrador? Esta accion es irreversible pero NO publica nada.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-trash me-1"></i> Descartar borrador
                    </button>
                </form>

                <form method="POST"
                      action="{{ route('admin.observations.response.publish', $observation) }}"
                      onsubmit="return confirm('Publicar la respuesta? Se notificara al ciudadano por correo y NO podras editarla mas.');">
                    @csrf
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-send-check me-1"></i> Publicar y notificar al ciudadano
                    </button>
                </form>
            </div>
        @else
            {{-- Sin respuesta: form de creacion --}}
            <form method="POST" action="{{ route('admin.observations.response.store', $observation) }}"
                  id="form-create-response">
                @csrf

                <div class="mb-3">
                    <x-input-label for="response_content" value="Contenido de la respuesta *" />
                    <textarea id="response_content" name="content" class="form-control" rows="6"
                              required minlength="10" maxlength="5000"
                              placeholder="Redacta la respuesta institucional. Minimo 10 caracteres.">{{ old('content') }}</textarea>
                    <div class="form-text">
                        Minimo 10, maximo 5.000 caracteres.
                    </div>
                    <x-input-error :messages="$errors->get('content')" />
                </div>

                <div class="d-flex justify-content-end gap-2 flex-wrap">
                    <button type="submit" name="publish" value="0" class="btn btn-outline-primary">
                        <i class="bi bi-save me-1"></i> Guardar como borrador
                    </button>
                    <button type="submit" name="publish" value="1" class="btn btn-success"
                            onclick="return confirm('Publicar directamente? Se notificara al ciudadano por correo y la respuesta sera inmutable.');">
                        <i class="bi bi-send-check me-1"></i> Publicar y notificar al ciudadano
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
