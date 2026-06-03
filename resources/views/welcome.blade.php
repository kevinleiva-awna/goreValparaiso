<x-public-layout>
    @section('title', 'Inicio')

    {{-- ═══ HERO ════════════════════════════════════════════════════════════
         Eyebrow + headline + lead + CTAs. Columna derecha: skyline SVG
         (o foto si existe en public/img/stock/hero-valparaiso.jpg).
    ════════════════════════════════════════════════════════════════════════ --}}
    <section class="gore-hero">
        <div class="container">
            <div class="row align-items-center g-4 g-lg-5">
                <div class="col-lg-7 gore-reveal">
                    <div class="gore-hero-eyebrow">
                        Participaci&oacute;n Ciudadana
                        <span class="gore-hero-eyebrow-stat">
                            &middot; Gobierno Regional de Valpara&iacute;so
                        </span>
                    </div>

                    <h1 class="gore-hero-title">
                        Participa en las decisiones
                        territoriales de Valpara&iacute;so.
                    </h1>

                    <p class="gore-hero-lead">
                        Env&iacute;a observaciones formales a los planes regulatorios y de
                        ordenamiento que se debaten hoy en la regi&oacute;n. Tu observaci&oacute;n
                        queda en el expediente oficial del proceso.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="#consultas" class="gore-hero-cta-primary">
                            Ver consultas vigentes
                            <span class="gore-hero-cta-chevron">&rarr;</span>
                        </a>
                        <a href="#como-funciona" class="gore-hero-cta-secondary">
                            C&oacute;mo funciona
                        </a>
                    </div>
                </div>

                {{-- Card destacada del proceso activo mas urgente.
                     Util + visualmente rica. Solo aparece si hay proceso. --}}
                @php
                    $featuredProcess = $featured
                        ->where('status', 'active')
                        ->sortBy(function ($c) {
                            return $c->ends_at ? $c->ends_at->timestamp : PHP_INT_MAX;
                        })
                        ->first();

                    if ($featuredProcess) {
                        $fpDaysLeft = $featuredProcess->ends_at
                            ? max(0, floor(now()->diffInDays($featuredProcess->ends_at, false)))
                            : null;
                        $fpTotalDays = $featuredProcess->starts_at && $featuredProcess->ends_at
                            ? max(1, $featuredProcess->starts_at->diffInDays($featuredProcess->ends_at))
                            : null;
                        $fpElapsedDays = $featuredProcess->starts_at && $fpTotalDays
                            ? max(0, min($fpTotalDays, floor($featuredProcess->starts_at->diffInDays(now(), false))))
                            : 0;
                        $fpProgress = $fpTotalDays ? round(($fpElapsedDays / $fpTotalDays) * 100) : 0;
                        $fpUrgency = match (true) {
                            $fpDaysLeft === null => 'var(--gore-ink-soft)',
                            $fpDaysLeft < 3 => '#dc2626',
                            $fpDaysLeft <= 7 => '#d97706',
                            default => '#059669',
                        };
                    }
                @endphp

                @if ($featuredProcess)
                    <div class="col-lg-5 gore-reveal" style="transition-delay: 200ms;">
                        <a href="{{ route('public.consultations.show', $featuredProcess->slug) }}"
                           class="gore-featured-process">
                            <div class="d-flex align-items-start justify-content-between mb-3">
                                <x-instrument-icon :type="$featuredProcess->instrument_type" :size="48" />
                                <span class="gore-badge gore-badge-success">
                                    <span class="gore-featured-pulse"></span>
                                    {{ $featuredProcess->instrument_type }} en curso
                                </span>
                            </div>

                            <h2 class="h5 fw-semibold mb-3" style="color: var(--gore-ink); letter-spacing: -0.01em;">
                                {{ $featuredProcess->title }}
                            </h2>

                            @if ($fpDaysLeft !== null && $fpTotalDays)
                                <div class="mb-2 small">
                                    <span style="color: {{ $fpUrgency }}; font-weight: 600;">
                                        {{ $fpDaysLeft }} días restantes
                                    </span>
                                </div>
                                <div class="gore-featured-progress" aria-hidden="true">
                                    <div class="gore-featured-progress-bar"
                                         style="width: {{ $fpProgress }}%; background: {{ $fpUrgency }};"></div>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between gap-3 pt-3 mt-3 small"
                                 style="border-top: 1px solid var(--gore-border);">
                                <div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Periodo</div>
                                    <div class="fw-semibold" style="color: var(--gore-ink);">
                                        {{ $featuredProcess->starts_at?->format('d/m') ?? '—' }}
                                        &ndash;
                                        {{ $featuredProcess->ends_at?->format('d/m/Y') ?? '—' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Observaciones</div>
                                    <div class="fw-semibold" style="color: var(--gore-ink);">
                                        {{ number_format($featuredProcess->observations_count, 0, ',', '.') }}
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold d-inline-flex align-items-center"
                                         style="color: var(--gore-primary);">
                                        Participar
                                        <i class="bi bi-arrow-right ms-1 gore-featured-chevron"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </section>

    {{-- ═══ CONSULTAS VIGENTES ═══════════════════════════════════════════════
         Grid de cards con icono distintivo por tipo de instrumento.
    ════════════════════════════════════════════════════════════════════════ --}}
    <section class="py-5 py-lg-6" id="consultas">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4 gap-3 gore-reveal">
                <div>
                    <h2 class="h3 fw-semibold mb-1">Consultas vigentes</h2>
                    <p class="text-muted small mb-0">
                        Procesos abiertos para enviar observaciones ciudadanas.
                    </p>
                </div>
                <a href="{{ route('public.consultations.index') }}" class="text-decoration-none small fw-semibold"
                   style="color: var(--gore-primary);">
                    Ver todos los procesos <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            @if ($featured->isEmpty())
                <div class="py-5 text-center"
                     style="border: 1px solid var(--gore-border); border-radius: var(--bs-border-radius-sm); background: #fff;">
                    <p class="text-muted mb-0">No hay consultas vigentes en este momento.</p>
                </div>
            @else
                <div class="row g-3 gore-reveal">
                    @foreach ($featured as $c)
                        @php
                            $daysLeft = $c->ends_at ? max(0, floor(now()->diffInDays($c->ends_at, false))) : null;
                            $isOpen = $c->status === 'active';
                            $urgencyColor = match (true) {
                                ! $isOpen || $daysLeft === null => null,
                                $daysLeft < 3 => '#dc2626',
                                $daysLeft <= 7 => '#d97706',
                                default => 'var(--gore-ink-soft)',
                            };
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('public.consultations.show', $c->slug) }}"
                               class="gore-consultation-card">
                                <div class="d-flex align-items-start justify-content-between mb-2">
                                    <x-instrument-icon :type="$c->instrument_type" :size="48" />
                                    @if ($isOpen)
                                        <span class="gore-badge gore-badge-success">Activa</span>
                                    @else
                                        <span class="gore-badge gore-badge-info">Por iniciar</span>
                                    @endif
                                </div>
                                <div class="gore-consultation-meta" style="margin-bottom: 0;">
                                    <span class="gore-badge gore-badge-brand">{{ $c->instrument_type }}</span>
                                </div>
                                <h3 class="gore-consultation-title">{{ $c->title }}</h3>
                                @if ($c->summary)
                                    <p class="mb-0 small text-muted"
                                       style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $c->summary }}
                                    </p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-auto gap-2 flex-wrap"
                                     style="border-top: 1px solid var(--gore-border);">
                                    <span class="small d-flex align-items-center gap-2 flex-wrap"
                                          style="color: var(--gore-ink-soft);">
                                        @if ($isOpen && $daysLeft !== null && $daysLeft > 0)
                                            <span style="@if($urgencyColor) color: {{ $urgencyColor }};@endif">
                                                {{ $daysLeft }} {{ $daysLeft === 1.0 ? 'día' : 'días' }} restantes
                                            </span>
                                        @elseif ($c->starts_at)
                                            <span>Inicia {{ $c->starts_at->format('d/m/Y') }}</span>
                                        @endif
                                        @if ($c->observations_count > 0)
                                            <span class="text-muted">
                                                &middot; {{ number_format($c->observations_count, 0, ',', '.') }} obs.
                                            </span>
                                        @endif
                                    </span>
                                    <span class="small fw-semibold" style="color: var(--gore-primary);">
                                        Participar <i class="bi bi-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ═══ BANNER INSTITUCIONAL ═════════════════════════════════════════════
         Logo_C_Core (banner triple a color: escudo + slogan + CORE) como
         elemento grafico grande. Da contexto institucional sin texto extra.
         Esta es la pieza visual distintiva del nuevo home.
    ════════════════════════════════════════════════════════════════════════ --}}
    <section class="py-5 py-lg-6 gore-reveal" id="institucional"
             style="border-top: 1px solid var(--gore-border);
                    border-bottom: 1px solid var(--gore-border);">
        <div class="container text-center">
            <div class="gore-hero-eyebrow justify-content-center mb-4" style="display: inline-flex;">
                Una iniciativa institucional
            </div>

            <div class="mb-4 d-flex justify-content-center">
                @php
                    $logoTriple = public_path('img/brand/Logo_C_Core.png');
                @endphp
                @if (file_exists($logoTriple))
                    <img src="{{ asset('img/brand/Logo_C_Core.png') }}"
                         alt="Gobierno Regional de Valpara&iacute;so · Regi&oacute;n de Derechos · Consejo Regional CORE Valpara&iacute;so"
                         style="max-width: 100%; height: auto; max-height: 120px; width: auto;">
                @else
                    <x-application-logo background="light" variant="triple" :height="100" />
                @endif
            </div>

            <p class="mx-auto" style="max-width: 56ch; color: var(--gore-ink-soft); line-height: 1.6;">
                Plataforma del <strong style="color: var(--gore-ink);">Gobierno Regional de Valpara&iacute;so</strong>
                desarrollada en el marco del programa <em>Regi&oacute;n de Derechos</em>, con la
                colaboraci&oacute;n del <strong style="color: var(--gore-ink);">Consejo Regional (CORE Valpara&iacute;so)</strong>
                para fortalecer la participaci&oacute;n ciudadana en los instrumentos de
                planificaci&oacute;n territorial de la regi&oacute;n.
            </p>
        </div>
    </section>

    {{-- ═══ CÓMO PARTICIPAR ══════════════════════════════════════════════════
         4 pasos en grid sobrio, sin animaciones gigantes ni numeros con
         gradient. Estilo Estonia tabular.
    ════════════════════════════════════════════════════════════════════════ --}}
    <section class="py-5 py-lg-6" id="como-funciona">
        <div class="container">
            <div class="row mb-4 gore-reveal">
                <div class="col-lg-8">
                    <h2 class="h3 fw-semibold mb-2">C&oacute;mo participar</h2>
                    <p class="text-muted mb-0">
                        En cuatro pasos llegas a enviar tu observaci&oacute;n. La revisamos,
                        queda en el expediente del proceso y te respondemos por correo
                        cuando corresponda.
                    </p>
                </div>
            </div>

            <div class="row g-3 gore-reveal">
                @foreach ([
                    ['n' => '01', 'title' => 'Selecciona la consulta', 'text' => 'Revisa los procesos vigentes y elige el que te interesa.'],
                    ['n' => '02', 'title' => 'Revisa los antecedentes', 'text' => 'Descarga la documentaci&oacute;n t&eacute;cnica oficial.'],
                    ['n' => '03', 'title' => 'Identif&iacute;cate', 'text' => 'Ingresa con ClaveUnica o como invitado/a (persona natural, jur&iacute;dica u organizaci&oacute;n).'],
                    ['n' => '04', 'title' => 'Env&iacute;a tu observaci&oacute;n', 'text' => 'Redacta tu observaci&oacute;n. Recibir&aacute;s confirmaci&oacute;n por correo.'],
                ] as $step)
                    <div class="col-md-6 col-lg-3">
                        <div style="border-top: 2px solid var(--gore-primary); padding-top: 1.25rem; height: 100%;">
                            <div class="small fw-semibold mb-2" style="color: var(--gore-primary); letter-spacing: 0.05em;">
                                Paso {{ $step['n'] }}
                            </div>
                            <h3 class="h6 fw-semibold mb-2">{!! $step['title'] !!}</h3>
                            <p class="small text-muted mb-0" style="line-height: 1.55;">{!! $step['text'] !!}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ═══ CTA FINAL CON LOGO INSTITUCIONAL ═════════════════════════════════
         Banda azul institucional con Logo_E_core (banner triple blanco
         para fondo oscuro) centrado, headline y CTA hacia consultas.
    ════════════════════════════════════════════════════════════════════════ --}}
    <section class="py-5 py-lg-6" style="background: var(--gore-primary); color: #fff;">
        <div class="container">
            <div class="row align-items-center g-4 gore-reveal">
                <div class="col-lg-6">
                    @php
                        $logoWhite = public_path('img/brand/Logo_E_core.png');
                    @endphp
                    @if (file_exists($logoWhite))
                        <img src="{{ asset('img/brand/Logo_E_core.png') }}"
                             alt="Gobierno Regional de Valpara&iacute;so · Consejo Regional CORE"
                             style="max-width: 100%; height: auto; max-height: 80px; width: auto;"
                             class="mb-4">
                    @endif

                    <h2 class="h3 fw-semibold mb-2" style="color: #fff;">
                        Listo para participar.
                    </h2>
                    <p class="mb-0" style="color: rgba(255,255,255,0.8); max-width: 50ch;">
                        Revisa las consultas vigentes y env&iacute;a tu observaci&oacute;n en pocos minutos.
                        Identif&iacute;cate con ClaveUnica o como invitado/a.
                    </p>
                </div>
                <div class="col-lg-6 d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="#consultas" class="btn btn-light"
                       style="font-weight: 500; padding: 0.8125rem 1.5rem; border-radius: var(--bs-border-radius-sm);">
                        Ver consultas vigentes <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                    @guest
                        <a href="{{ route('citizen.claveunica.redirect') }}"
                           class="btn btn-outline-light"
                           style="font-weight: 500; padding: 0.8125rem 1.5rem; border-radius: var(--bs-border-radius-sm);">
                            <i class="bi bi-shield-check me-1"></i> Ingresar con ClaveUnica
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
