<x-public-layout>
    @section('title', $consultation->title)
    @section('meta_description', $consultation->summary ?? 'Proceso de consulta publica del Gobierno Regional de Valparaiso.')

    @php
        $daysLeft = $consultation->ends_at ? now()->diffInDays($consultation->ends_at, false) : null;
        $isOpen = $consultation->status === 'active';
        $isClosed = $consultation->status === 'closed';
        $statusClass = match($consultation->status) {
            'active' => 'gore-badge-success',
            'published' => 'gore-badge-info',
            'closed' => 'gore-badge-muted',
            default => 'gore-badge-muted',
        };
        $statusLabel = match($consultation->status) {
            'active' => 'Consulta activa',
            'published' => 'Proximamente',
            'closed' => 'Consulta cerrada',
            default => $consultation->status,
        };
    @endphp

    {{-- Hero del proceso --}}
    <section class="bg-white border-bottom" style="border-color: var(--gore-border) !important;">
        <div class="container py-5">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('home') }}" class="text-muted text-decoration-none">Inicio</a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="{{ route('public.consultations.index') }}" class="text-muted text-decoration-none">
                            Consultas publicas
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        {{ Str::limit($consultation->title, 40) }}
                    </li>
                </ol>
            </nav>

            <div class="row align-items-end">
                <div class="col-lg-8">
                    <div class="mb-3">
                        <span class="gore-badge gore-badge-brand me-1">{{ $consultation->instrument_type }}</span>
                        <span class="gore-badge {{ $statusClass }}">
                            @if ($isOpen)
                                <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                            @endif
                            {{ $statusLabel }}
                        </span>
                    </div>
                    <h1 class="display-5 fw-bold mb-3" style="letter-spacing: -0.02em;">
                        {{ $consultation->title }}
                    </h1>
                    @if ($consultation->summary)
                        <p class="lead text-muted mb-0">{{ $consultation->summary }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Indicadores destacados --}}
    @if ($isOpen && $daysLeft !== null && $daysLeft > 0)
        <section style="background: linear-gradient(135deg, var(--gore-primary-dark) 0%, var(--gore-primary) 100%);" class="py-4">
            <div class="container">
                <div class="row text-white align-items-center g-3">
                    <div class="col-md-3 text-center">
                        <div class="display-4 fw-bold" style="letter-spacing: -0.03em;">
                            {{ floor($daysLeft) }}
                        </div>
                        <div class="small text-uppercase" style="letter-spacing: 0.05em; opacity: 0.85;">
                            {{ floor($daysLeft) === 1 ? 'dia restante' : 'dias restantes' }}
                        </div>
                    </div>
                    <div class="col-md-3 text-center">
                        <div class="display-4 fw-bold" style="letter-spacing: -0.03em;">
                            {{ $consultation->observations_count }}
                        </div>
                        <div class="small text-uppercase" style="letter-spacing: 0.05em; opacity: 0.85;">
                            Observaciones
                        </div>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <a href="#participar" class="btn btn-light btn-lg fw-semibold">
                            <i class="bi bi-pencil-square me-1"></i>
                            Enviar mi observacion
                        </a>
                    </div>
                </div>
            </div>
        </section>
    @endif

    {{-- Contenido principal --}}
    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-8">
                    {{-- Descripcion --}}
                    @if ($consultation->description)
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4 p-md-5">
                                <h2 class="h4 mb-3">Sobre este proceso</h2>
                                <div class="text-prewrap" style="color: var(--gore-ink-soft); line-height: 1.7;">{{ $consultation->description }}</div>
                            </div>
                        </div>
                    @endif

                    {{-- Etapas --}}
                    @if ($consultation->stages->isNotEmpty())
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body p-4 p-md-5">
                                <h2 class="h4 mb-4">Etapas del proceso</h2>
                                <div class="d-flex flex-column gap-3">
                                    @foreach ($consultation->stages as $stage)
                                        @php
                                            $stageStatus = $stage->status;
                                            $stageColor = match($stageStatus) {
                                                'active' => 'var(--gore-success)',
                                                'closed' => 'var(--gore-ink-soft)',
                                                default => 'var(--gore-border-strong)',
                                            };
                                        @endphp
                                        <div class="d-flex gap-3">
                                            <div class="flex-shrink-0 d-flex flex-column align-items-center">
                                                <div class="d-flex align-items-center justify-content-center fw-bold"
                                                     style="width: 36px; height: 36px; border-radius: 50%;
                                                            background: {{ $stageColor }}; color: white;">
                                                    {{ $stage->position }}
                                                </div>
                                                @if (! $loop->last)
                                                    <div style="width: 2px; flex-grow: 1; background: var(--gore-border); margin-top: 4px;"></div>
                                                @endif
                                            </div>
                                            <div class="flex-grow-1 pb-3">
                                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                                    <div>
                                                        <h3 class="h6 mb-1">{{ $stage->name }}</h3>
                                                        <div class="small text-muted">
                                                            @if ($stage->starts_at && $stage->ends_at)
                                                                <i class="bi bi-calendar3 me-1"></i>
                                                                {{ $stage->starts_at->format('d/m/Y') }} —
                                                                {{ $stage->ends_at->format('d/m/Y') }}
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <div class="d-flex flex-column align-items-end gap-1">
                                                        @if ($stageStatus === 'active')
                                                            <span class="gore-badge gore-badge-success">En curso</span>
                                                        @elseif ($stageStatus === 'closed')
                                                            <span class="gore-badge gore-badge-muted">Finalizada</span>
                                                        @else
                                                            <span class="gore-badge gore-badge-info">Pendiente</span>
                                                        @endif
                                                        @if (! $stage->accepts_observations)
                                                            <span class="small text-muted">
                                                                <i class="bi bi-info-circle me-1"></i>
                                                                Solo informativa
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                                @if ($stage->description)
                                                    <p class="small text-muted mt-2 mb-0">{{ $stage->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- BLOQUE PARTICIPACION ========================================
                         Renderiza distinto segun el estado del gatekeeper:
                         - gate.can=true              -> form de envio de observacion
                         - gate.reason=guest          -> CTA login/registro
                         - gate.reason=not_verified   -> CTA verificar correo
                         - gate.reason=not_open       -> mensaje de proceso cerrado
                         - gate.reason=wrong_auth_method -> mensaje de incompatibilidad
                         - gate.reason=wrong_role     -> CTA salir de staff
                       =============================================================== --}}

                    @if ($gate['can'])
                        {{-- Form de envio --}}
                        <div id="participar" class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white border-bottom py-3">
                                <h2 class="h5 mb-0 d-flex align-items-center">
                                    <i class="bi bi-pencil-square me-2" style="color: var(--gore-primary);"></i>
                                    Enviar mi observacion
                                </h2>
                            </div>
                            <div class="card-body p-4 p-md-5">
                                <div class="alert alert-success small d-flex mb-4">
                                    <i class="bi bi-shield-check me-2 flex-shrink-0" style="font-size: 1.1rem;"></i>
                                    <div>
                                        Estas identificado(a) como
                                        <strong>{{ Auth::user()->name }} {{ Auth::user()->last_name }}</strong>
                                        ({{ Auth::user()->email }}).
                                        Tu observacion quedara registrada con timestamp inalterable.
                                    </div>
                                </div>

                                <form method="POST"
                                      action="{{ route('public.observations.store', $consultation->slug) }}">
                                    @csrf

                                    <div class="mb-3">
                                        <x-input-label for="obs_subject" value="Asunto (opcional)" />
                                        <x-text-input id="obs_subject" name="subject" type="text"
                                                      :value="old('subject')" maxlength="255"
                                                      placeholder="Ej: Observacion sobre el uso de suelo en Concon" />
                                        <x-input-error :messages="$errors->get('subject')" />
                                    </div>

                                    <div class="mb-3">
                                        <x-input-label for="obs_category" value="Categoria (opcional)" />
                                        <select id="obs_category" name="category" class="form-select">
                                            <option value="">Sin categoria especifica</option>
                                            @foreach (['Uso de suelo', 'Vialidad', 'Areas verdes', 'Patrimonio', 'Equipamiento', 'Riesgo natural', 'Otro'] as $cat)
                                                <option value="{{ $cat }}" @selected(old('category') === $cat)>{{ $cat }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('category')" />
                                    </div>

                                    <div class="mb-3">
                                        <x-input-label for="obs_body" value="Tu observacion *" />
                                        <textarea id="obs_body" name="body" class="form-control" rows="8"
                                                  required minlength="10" maxlength="10000"
                                                  placeholder="Describe tu observacion con el mayor detalle posible. Minimo 10 caracteres, maximo 10.000.">{{ old('body') }}</textarea>
                                        <div class="form-text">
                                            <span id="obs_charcount">0</span> / 10.000 caracteres
                                        </div>
                                        <x-input-error :messages="$errors->get('body')" />
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                        <p class="small text-muted mb-0">
                                            Al enviar aceptas que tu observacion sera parte del expediente
                                            publico del proceso.
                                        </p>
                                        <x-primary-button class="btn-lg">
                                            <i class="bi bi-send me-1"></i> Enviar observacion
                                        </x-primary-button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            (function () {
                                const txt = document.getElementById('obs_body');
                                const counter = document.getElementById('obs_charcount');
                                if (!txt || !counter) return;
                                const update = () => counter.textContent = txt.value.length.toLocaleString('es-CL');
                                txt.addEventListener('input', update);
                                update();
                            })();
                        </script>
                    @else
                        {{-- Gate cerrado: mensaje contextual segun la razon --}}
                        <div id="participar" class="card border-0 shadow-sm mb-4"
                             style="background: linear-gradient(135deg, var(--gore-primary-dark) 0%, var(--gore-primary) 100%); color: white;">
                            <div class="card-body p-4 p-md-5 text-center">
                                @switch($gate['reason'])
                                    @case('guest')
                                        <h2 class="h3 fw-bold mb-2">Participa en este proceso</h2>
                                        <p class="mb-4" style="opacity: 0.85;">
                                            Para enviar una observacion necesitas identificarte.
                                            Tu identidad queda asociada de forma inalterable a lo que envies.
                                        </p>
                                        <div class="d-flex flex-column flex-md-row gap-3 justify-content-center">
                                            <a href="{{ route('citizen.claveunica.redirect') }}"
                                               class="btn btn-light btn-lg fw-semibold">
                                                <i class="bi bi-shield-check me-1"></i> Ingresar con ClaveUnica
                                            </a>
                                            <a href="{{ route('citizen.login') }}" class="btn btn-outline-light btn-lg">
                                                Ingresar con correo
                                            </a>
                                        </div>
                                        <p class="small mt-3 mb-0" style="opacity: 0.7;">
                                            ¿Primera vez? <a href="{{ route('citizen.register') }}" class="text-white fw-semibold">
                                                Crear cuenta
                                            </a>
                                        </p>
                                        @break

                                    @case('not_verified')
                                        <h2 class="h3 fw-bold mb-2">Verifica tu correo para participar</h2>
                                        <p class="mb-4" style="opacity: 0.85;">
                                            Te enviamos un enlace de verificacion a tu correo. Una vez verificado,
                                            podras enviar observaciones a esta consulta.
                                        </p>
                                        <a href="{{ route('citizen.verification.notice') }}"
                                           class="btn btn-light btn-lg fw-semibold">
                                            <i class="bi bi-envelope-check me-1"></i> Ir a verificar mi correo
                                        </a>
                                        @break

                                    @case('not_open')
                                        @if ($isClosed)
                                            <h2 class="h3 fw-bold mb-2">Proceso cerrado</h2>
                                            <p class="mb-0" style="opacity: 0.85;">
                                                La ventana de participacion termino el {{ $consultation->ends_at?->format('d/m/Y') }}.
                                                Puedes consultar los antecedentes del proceso a continuacion.
                                            </p>
                                        @else
                                            <h2 class="h3 fw-bold mb-2">Participacion no habilitada aun</h2>
                                            <p class="mb-0" style="opacity: 0.85;">
                                                Este proceso aun no esta en periodo de recepcion de observaciones.
                                                Vuelve a revisar pronto.
                                            </p>
                                        @endif
                                        @break

                                    @case('wrong_auth_method')
                                        <h2 class="h3 fw-bold mb-2">Metodo de identificacion no admitido</h2>
                                        <p class="mb-0" style="opacity: 0.85;">
                                            Esta consulta requiere identificacion via ClaveUnica.
                                            Por favor cierra sesion y vuelve a ingresar usando ClaveUnica.
                                        </p>
                                        @break

                                    @case('wrong_role')
                                        <h2 class="h3 fw-bold mb-2">Cuenta institucional</h2>
                                        <p class="mb-0" style="opacity: 0.85;">
                                            Estas autenticado como funcionario. Para participar como ciudadano(a),
                                            cierra sesion del backoffice y registrate por separado.
                                        </p>
                                        @break

                                    @default
                                        <h2 class="h3 fw-bold mb-2">No es posible enviar observaciones</h2>
                                @endswitch
                            </div>
                        </div>
                    @endif

                    {{-- Respuestas institucionales publicadas (D14). Solo se muestran
                         cuando hay al menos una respuesta con status=published. --}}
                    @include('public.consultas._responses', [
                        'publishedResponses' => $publishedResponses,
                        'consultation' => $consultation,
                    ])
                </div>

                {{-- Sidebar --}}
                <div class="col-lg-4">
                    {{-- Antecedentes tecnicos --}}
                    <div class="card border-0 shadow-sm mb-3 sticky-top" style="top: 1rem; z-index: 10;">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-0 d-flex align-items-center">
                                <i class="bi bi-file-earmark-text me-2" style="color: var(--gore-primary);"></i>
                                Antecedentes tecnicos
                                <span class="gore-badge gore-badge-muted ms-auto">
                                    {{ $consultation->documents->count() }}
                                </span>
                            </h2>
                        </div>

                        @if ($consultation->documents->isEmpty())
                            <div class="card-body text-center py-4">
                                <p class="small text-muted mb-0 fst-italic">
                                    Aun no hay documentos publicados para este proceso.
                                </p>
                            </div>
                        @else
                            <ul class="list-group list-group-flush">
                                @foreach ($consultation->documents as $doc)
                                    @php
                                        $bytes = $doc->size_bytes;
                                        $sizeFmt = $bytes >= 1048576
                                            ? round($bytes / 1048576, 1) . ' MB'
                                            : ($bytes >= 1024 ? round($bytes / 1024, 1) . ' KB' : $bytes . ' B');
                                        $ext = strtoupper(pathinfo($doc->original_filename, PATHINFO_EXTENSION));
                                    @endphp
                                    <li class="list-group-item">
                                        <a href="{{ route('public.consultations.documents.download', [$consultation->slug, $doc->file_group_id]) }}"
                                           class="d-flex align-items-start text-decoration-none">
                                            <div class="me-2 d-flex align-items-center justify-content-center flex-shrink-0"
                                                 style="width: 36px; height: 36px; background: rgba(21,28,104,0.08); color: var(--gore-primary); border-radius: 8px; font-size: 0.7rem; font-weight: 700;">
                                                {{ $ext }}
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold small" style="color: var(--gore-ink);">
                                                    {{ $doc->title }}
                                                </div>
                                                <div class="small text-muted">
                                                    {{ $sizeFmt }}
                                                    @if ($doc->version > 1)
                                                        &middot; v{{ $doc->version }}
                                                    @endif
                                                </div>
                                            </div>
                                            <i class="bi bi-download text-muted ms-2"></i>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    {{-- Datos del proceso --}}
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white border-bottom py-3">
                            <h2 class="h6 mb-0">Datos del proceso</h2>
                        </div>
                        <div class="card-body small">
                            <dl class="mb-0">
                                <dt class="text-muted small">Tipo de instrumento</dt>
                                <dd class="mb-3">{{ $consultation->instrument_type }}</dd>

                                @if ($consultation->starts_at)
                                    <dt class="text-muted small">Inicio del proceso</dt>
                                    <dd class="mb-3">{{ $consultation->starts_at->format('d \d\e F \d\e Y') }}</dd>
                                @endif

                                @if ($consultation->ends_at)
                                    <dt class="text-muted small">Termino del proceso</dt>
                                    <dd class="mb-3">{{ $consultation->ends_at->format('d \d\e F \d\e Y') }}</dd>
                                @endif

                                <dt class="text-muted small">Metodos de identificacion</dt>
                                <dd class="mb-0">
                                    @foreach ((array) $consultation->auth_methods as $method)
                                        @if ($method === 'claveunica')
                                            <span class="gore-badge gore-badge-info">ClaveUnica</span>
                                        @elseif ($method === 'manual')
                                            <span class="gore-badge gore-badge-info">Registro manual</span>
                                        @endif
                                    @endforeach
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
