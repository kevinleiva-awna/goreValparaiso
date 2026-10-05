<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="h3 mb-0">Eliminar consulta definitivamente</h1>
            <a href="{{ route('admin.consultations.index', ['archived' => 1]) }}"
               class="small text-muted text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Volver a las consultas archivadas
            </a>
        </div>
    </x-slot>

    <div class="container py-4" style="max-width: 820px;">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted small mb-1">Consulta archivada</p>
                <h2 class="h5 mb-4">{{ $consultation->title }}</h2>

                <div class="alert alert-danger">
                    <p class="fw-semibold mb-2">
                        <i class="bi bi-exclamation-octagon me-1"></i>
                        Esta accion no se puede deshacer. Se borrara para siempre:
                    </p>
                    <ul class="mb-0">
                        <li>
                            <strong>{{ $impact['observaciones'] }}</strong> observacion(es) ciudadana(s)
                            @if ($impact['observaciones_archivadas'] > 0)
                                (incluye {{ $impact['observaciones_archivadas'] }} archivada(s))
                            @endif
                        </li>
                        <li>
                            <strong>{{ $impact['respuestas_publicadas'] }}</strong> respuesta(s) institucional(es) publicada(s)
                            y <strong>{{ $impact['respuestas_borrador'] }}</strong> borrador(es)
                        </li>
                        <li>
                            <strong>{{ $impact['adjuntos'] }}</strong> archivo(s) adjunto(s) de la ciudadania
                            @if ($impact['adjuntos_bytes'] >= 1048576)
                                ({{ number_format($impact['adjuntos_bytes'] / 1048576, 1, ',', '.') }} MB)
                            @elseif ($impact['adjuntos_bytes'] > 0)
                                ({{ max(1, round($impact['adjuntos_bytes'] / 1024)) }} KB)
                            @endif
                        </li>
                        <li>
                            <strong>{{ $impact['antecedentes'] }}</strong> antecedente(s) tecnico(s), con todas sus versiones
                        </li>
                        <li>La ficha publica de la consulta y sus respuestas publicadas</li>
                    </ul>
                </div>

                @if ($impact['observaciones'] > 0)
                    <div class="alert alert-warning small">
                        <p class="mb-2">
                            <strong>Antes de eliminar, descarga el expediente.</strong>
                            Despues no habra forma de recuperarlo desde la plataforma.
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('admin.observations.export', ['format' => 'xlsx', 'consultation_id' => $consultation->id]) }}"
                               class="btn btn-sm btn-outline-dark">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Compendio (.xlsx)
                            </a>
                            @if ($impact['adjuntos'] > 0)
                                <a href="{{ route('admin.observations.attachments.zip', ['consultation_id' => $consultation->id]) }}"
                                   class="btn btn-sm btn-outline-dark">
                                    <i class="bi bi-file-earmark-zip me-1"></i> Adjuntos en ZIP
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.consultations.force-delete', $consultation) }}" class="mt-4">
                    @csrf
                    @method('DELETE')

                    <label for="confirmacion" class="form-label">
                        Para confirmar, escribe <strong>ELIMINAR</strong>
                    </label>
                    <input type="text" id="confirmacion" name="confirmacion" autocomplete="off"
                           class="form-control @error('confirmacion') is-invalid @enderror"
                           style="max-width: 280px;">
                    @error('confirmacion')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('admin.consultations.index', ['archived' => 1]) }}"
                           class="btn btn-outline-secondary">Cancelar</a>
                        <button class="btn btn-danger">
                            <i class="bi bi-trash3 me-1"></i> Eliminar definitivamente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <p class="text-center text-muted small mt-3 mb-0">
            <i class="bi bi-shield-check me-1"></i>
            La eliminacion queda registrada en la bitacora con tu nombre y el resumen de lo borrado.
        </p>
    </div>
</x-app-layout>
