<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h3 mb-0">Responder en lote</h1>
                <p class="text-muted small mb-0">
                    Mismo texto enviado como respuesta institucional a multiples observaciones.
                </p>
            </div>
            <a href="{{ route('admin.observations.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver al listado
            </a>
        </div>
    </x-slot>

    <div class="container py-4">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (count($alreadyResponded) > 0)
            <div class="alert alert-warning small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                <strong>Atencion:</strong> {{ count($alreadyResponded) }} observacion(es) seleccionada(s)
                ya tienen respuesta y seran excluidas. Si continuas, solo se respondera al resto.
            </div>
        @endif

        @if ($observations->isEmpty())
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5 text-center text-muted">
                    <i class="bi bi-inbox display-6 d-block mb-2"></i>
                    No seleccionaste observaciones desde el listado.
                    <div class="mt-3">
                        <a href="{{ route('admin.observations.index') }}" class="btn btn-outline-primary btn-sm">
                            Volver al listado
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-0">
                                <i class="bi bi-pencil-square me-2" style="color: var(--gore-primary);"></i>
                                Redaccion de la respuesta
                            </h2>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" action="{{ route('admin.observations.batch.store') }}">
                                @csrf

                                @foreach ($observations as $obs)
                                    @if (! in_array($obs->id, $alreadyResponded, true))
                                        <input type="hidden" name="observation_ids[]" value="{{ $obs->id }}">
                                    @endif
                                @endforeach

                                <div class="mb-3">
                                    <x-input-label for="batch_content" value="Texto de la respuesta institucional *" />
                                    <textarea id="batch_content" name="content" class="form-control" rows="10"
                                              required minlength="10" maxlength="5000"
                                              placeholder="Redacta una respuesta unica que aplicara a todas las observaciones seleccionadas.">{{ old('content') }}</textarea>
                                    <div class="form-text">
                                        Minimo 10, maximo 5.000 caracteres. Este texto se enviara por correo
                                        a cada ciudadano y quedara publicado en el portal publico.
                                    </div>
                                    <x-input-error :messages="$errors->get('content')" />
                                </div>

                                <div class="alert alert-info small d-flex">
                                    <i class="bi bi-info-circle me-2 flex-shrink-0" style="font-size: 1.1rem;"></i>
                                    <div>
                                        Las respuestas en lote se publican <strong>inmediatamente</strong>.
                                        No quedan en borrador. Cada ciudadano recibe el mismo texto por correo
                                        y todas las respuestas comparten un identificador de lote.
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('admin.observations.index') }}" class="btn btn-outline-secondary">
                                        Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-success"
                                            onclick="return confirm('Publicar la respuesta a todas las observaciones seleccionadas? Esta accion es irreversible.');">
                                        <i class="bi bi-send-check me-1"></i> Publicar lote y notificar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-0 d-flex align-items-center">
                                <i class="bi bi-list-check me-2" style="color: var(--gore-primary);"></i>
                                Observaciones del lote
                                <span class="gore-badge gore-badge-muted ms-auto">
                                    {{ $observations->count() - count($alreadyResponded) }} / {{ $observations->count() }}
                                </span>
                            </h2>
                        </div>
                        <ul class="list-group list-group-flush" style="max-height: 500px; overflow-y: auto;">
                            @foreach ($observations as $obs)
                                @php
                                    $excluded = in_array($obs->id, $alreadyResponded, true);
                                @endphp
                                <li class="list-group-item {{ $excluded ? 'bg-light' : '' }}">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div class="flex-grow-1">
                                            <div class="small fw-semibold {{ $excluded ? 'text-muted text-decoration-line-through' : '' }}">
                                                {{ $obs->snapshot_full_name }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ Str::limit($obs->subject ?? $obs->body, 60) }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ Str::limit($obs->consultation?->title, 50) }}
                                            </div>
                                        </div>
                                        @if ($excluded)
                                            <span class="gore-badge gore-badge-muted" style="white-space: nowrap;">
                                                Ya respondida
                                            </span>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
