<x-public-layout>
    @section('title', 'Inicio')

    {{-- HERO estilo Stripe — fondo claro con aurora calida derramandose
         desde la derecha. Headline grande con palabras destacadas en gradient
         multicolor coherente con el aurora. Stats reales al pie. --}}
    <section class="gore-hero">
        <div class="gore-hero-blob" aria-hidden="true"></div>

        <div class="container">
            <div class="row align-items-end g-4">
                <div class="col-lg-9 col-xl-8 gore-reveal gore-stagger">
                    {{-- Eyebrow estilo Stripe: texto plano con stat numerico al lado --}}
                    <div class="gore-hero-eyebrow">
                        Region de Valpara&iacute;so &middot; Participaci&oacute;n Ciudadana
                        <span class="gore-hero-eyebrow-stat">
                            &middot; {{ number_format($stats['total_observations'], 0, ',', '.') }}
                            observaciones registradas
                        </span>
                    </div>

                    <h1 class="gore-hero-title">
                        Tu voz construye el
                        <span class="gore-hero-accent">ordenamiento territorial</span>
                        de la regi&oacute;n.
                    </h1>

                    <p class="gore-hero-lead">
                        Plataforma oficial del Gobierno Regional para participar en
                        consultas p&uacute;blicas sobre Instrumentos de Planificaci&oacute;n y
                        Ordenamiento Territorial. Identif&iacute;cate con ClaveUnica o como
                        invitado/a en pocos pasos.
                    </p>

                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <a href="#consultas" class="gore-hero-cta-primary">
                            Ver consultas vigentes
                            <span class="gore-hero-cta-chevron">&rsaquo;</span>
                        </a>
                        <a href="#como-funciona" class="gore-hero-cta-secondary">
                            <i class="bi bi-info-circle"></i>
                            C&oacute;mo funciona
                        </a>
                    </div>

                    {{-- Stats inline al pie del hero, horizontales (no card oscura) --}}
                    <div class="d-flex flex-wrap gap-4 gap-md-5 mt-5 pt-4"
                         style="border-top: 1px solid rgba(15, 23, 42, 0.08);">
                        <div class="gore-stat">
                            <div class="gore-stat-value">{{ number_format($stats['active_processes'], 0, ',', '.') }}</div>
                            <div class="gore-stat-label">
                                {{ $stats['active_processes'] === 1 ? 'Proceso activo' : 'Procesos activos' }}
                            </div>
                        </div>
                        <div class="gore-stat">
                            <div class="gore-stat-value">{{ number_format($stats['total_observations'], 0, ',', '.') }}</div>
                            <div class="gore-stat-label">Observaciones ciudadanas</div>
                        </div>
                        <div class="gore-stat">
                            <div class="gore-stat-value">{{ number_format($stats['closed_processes'], 0, ',', '.') }}</div>
                            <div class="gore-stat-label">
                                {{ $stats['closed_processes'] === 1 ? 'Proceso cerrado' : 'Procesos cerrados' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- BENEFICIOS --}}
    <section class="py-5 py-lg-6 gore-aurora-bg" id="beneficios">
        <div class="container py-4">
            <div class="row mb-5 gore-reveal">
                <div class="col-lg-8 mx-auto text-center">
                    <span class="gore-badge gore-badge-brand mb-3">Por que participar</span>
                    <h2 class="h1 fw-bold mb-3" style="letter-spacing: -0.02em;">
                        Una plataforma diseñada para que tu opinion <span style="color: var(--gore-primary);">deje huella</span>.
                    </h2>
                    <p class="text-muted lead">
                        Robusta, accesible y con trazabilidad inalterable. Construida sobre
                        estandares de gobierno digital y seguridad institucional.
                    </p>
                </div>
            </div>

            <div class="row g-4 gore-reveal gore-stagger">
                <div class="col-md-6 col-lg-3">
                    <div class="gore-feature-card">
                        <div class="gore-feature-icon"><i class="bi bi-shield-check"></i></div>
                        <h3>Identidad verificada</h3>
                        <p>Identificate via ClaveUnica o participa como invitado/a (persona natural, juridica u organizacion) declarando tus datos.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gore-feature-card">
                        <div class="gore-feature-icon"><i class="bi bi-clock-history"></i></div>
                        <h3>Trazabilidad inalterable</h3>
                        <p>Cada observacion queda registrada con timestamp inmodificable y auditoria completa del proceso.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gore-feature-card">
                        <div class="gore-feature-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <h3>Antecedentes publicos</h3>
                        <p>Descarga toda la documentacion tecnica del proceso antes de formular tu observacion.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="gore-feature-card">
                        <div class="gore-feature-icon"><i class="bi bi-universal-access"></i></div>
                        <h3>Accesible para todos</h3>
                        <p>Cumple estandares WCAG 2.1 AA y lineamientos del Kit Digital del Gobierno de Chile.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CONSULTAS VIGENTES --}}
    <section class="py-5 py-lg-6 bg-white" id="consultas" style="border-top: 1px solid var(--gore-border);">
        <div class="container py-4">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-5 gap-3 gore-reveal">
                <div>
                    <span class="gore-badge gore-badge-brand mb-2">Procesos abiertos</span>
                    <h2 class="h1 fw-bold mb-2" style="letter-spacing: -0.02em;">Consultas vigentes</h2>
                    <p class="text-muted mb-0">Selecciona un proceso para revisar antecedentes y enviar tu observacion.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-funnel me-1"></i> Filtrar
                    </button>
                </div>
            </div>

            @if ($featured->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox display-6 text-muted d-block mb-3"></i>
                    <p class="text-muted">No hay consultas vigentes en este momento.</p>
                </div>
            @else
                <div class="row g-4 gore-reveal gore-stagger">
                    @foreach ($featured as $c)
                        @php
                            $daysLeft = $c->ends_at ? max(0, floor(now()->diffInDays($c->ends_at, false))) : null;
                            $isOpen = $c->status === 'active';
                            // Urgencia visual (acta junio 2026, punto 1):
                            // >7 dias verde, 3-7 dias ambar, <3 dias rojo.
                            $urgencyColor = match (true) {
                                ! $isOpen || $daysLeft === null => null,
                                $daysLeft < 3 => '#dc2626',
                                $daysLeft <= 7 => '#d97706',
                                default => '#059669',
                            };
                        @endphp
                        <div class="col-md-6 col-lg-4">
                            <a href="{{ route('public.consultations.show', $c->slug) }}"
                               class="gore-consultation-card">
                                <div class="gore-consultation-meta">
                                    <span class="gore-badge gore-badge-brand">{{ $c->instrument_type }}</span>
                                    @if ($isOpen)
                                        <span class="gore-badge gore-badge-success">
                                            <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                            Activa
                                        </span>
                                    @else
                                        <span class="gore-badge gore-badge-info">Por iniciar</span>
                                    @endif
                                </div>
                                <h3 class="gore-consultation-title">{{ $c->title }}</h3>
                                @if ($c->summary)
                                    <p class="mb-0 small text-muted" style="-webkit-line-clamp: 2; display: -webkit-box; -webkit-box-orient: vertical; overflow: hidden;">
                                        {{ $c->summary }}
                                    </p>
                                @endif
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-auto gap-2 flex-wrap"
                                     style="border-top: 1px solid var(--gore-border);">
                                    <span class="small d-flex align-items-center gap-2 flex-wrap"
                                          style="color: var(--gore-ink-soft);">
                                        @if ($isOpen && $daysLeft !== null && $daysLeft > 0)
                                            <span class="d-inline-flex align-items-center"
                                                  style="@if($urgencyColor) color: {{ $urgencyColor }}; font-weight: 600;@endif">
                                                <i class="bi bi-clock me-1"></i>
                                                {{ $daysLeft }} {{ $daysLeft === 1.0 ? 'dia' : 'dias' }} restantes
                                            </span>
                                        @elseif ($c->starts_at)
                                            <span>
                                                <i class="bi bi-calendar-event me-1"></i>
                                                Inicia {{ $c->starts_at->format('d/m/Y') }}
                                            </span>
                                        @endif
                                        @if ($c->observations_count > 0)
                                            <span class="text-muted">
                                                &middot;
                                                <i class="bi bi-chat-left-text me-1"></i>
                                                {{ number_format($c->observations_count, 0, ',', '.') }} obs.
                                            </span>
                                        @endif
                                    </span>
                                    <span class="small fw-semibold" style="color: var(--gore-primary);">
                                        Ver detalles <i class="bi bi-arrow-right ms-1"></i>
                                    </span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="text-center mt-5">
                <a href="{{ route('public.consultations.index') }}" class="btn btn-outline-primary">
                    Ver todas las consultas
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- COMO FUNCIONA --}}
    <section class="py-5 py-lg-6 gore-aurora-bg" id="como-funciona">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-5 gore-reveal">
                    <span class="gore-badge gore-badge-brand mb-3">Paso a paso</span>
                    <h2 class="h1 fw-bold mb-3" style="letter-spacing: -0.02em;">
                        Como participar en 4 pasos
                    </h2>
                    <p class="text-muted lead mb-4">
                        El proceso es simple y esta diseñado para que cualquier ciudadano
                        pueda hacer llegar su observacion al GORE de manera formal.
                    </p>
                    <a href="#consultas" class="btn btn-primary btn-lg">
                        Empezar ahora <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                <div class="col-lg-7">
                    <div class="row g-3 gore-reveal gore-stagger">
                        @foreach ([
                            ['n' => '01', 'title' => 'Selecciona la consulta', 'text' => 'Revisa las consultas vigentes y elige el proceso en el que quieres participar.'],
                            ['n' => '02', 'title' => 'Revisa los antecedentes', 'text' => 'Descarga los documentos tecnicos oficiales del instrumento.'],
                            ['n' => '03', 'title' => 'Identificate', 'text' => 'Ingresa con ClaveUnica o participa sin cuenta declarando nombre y correo. Persona natural, juridica u organizacion.'],
                            ['n' => '04', 'title' => 'Envia tu observacion', 'text' => 'Redacta tu observacion formal. Quedara con timestamp inalterable y recibiras confirmacion por correo.'],
                        ] as $step)
                            <div class="col-md-6">
                                <div class="gore-feature-card h-100">
                                    <div class="fw-bold mb-2" style="color: var(--gore-primary); font-size: 1.5rem; letter-spacing: -0.02em;">
                                        {{ $step['n'] }}
                                    </div>
                                    <h3 style="font-size: 1.0625rem;">{{ $step['title'] }}</h3>
                                    <p>{{ $step['text'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-5 py-lg-6 bg-white" id="preguntas" style="border-top: 1px solid var(--gore-border);">
        <div class="container py-4">
            <div class="row">
                <div class="col-lg-8 mx-auto gore-reveal">
                    <div class="text-center mb-5">
                        <span class="gore-badge gore-badge-brand mb-3">FAQ</span>
                        <h2 class="h1 fw-bold mb-2" style="letter-spacing: -0.02em;">Preguntas frecuentes</h2>
                    </div>

                    <div class="accordion accordion-flush" id="faq">
                        @foreach ([
                            ['q' => 'Quien puede participar en las consultas?', 'a' => 'Cualquier persona natural mayor de 14 anos puede participar identificandose via ClaveUnica o como invitado/a entregando nombre, correo y RUT/pasaporte. Tambien pueden participar personas juridicas y organizaciones sin personalidad juridica entregando razon social, RUT y correo.'],
                            ['q' => 'Puedo enviar mas de una observacion en una misma consulta?', 'a' => 'Si. Un mismo usuario puede enviar multiples observaciones a una misma consulta. Cada observacion se registra como un acto independiente y queda asociada a su identidad verificada.'],
                            ['q' => 'Que pasa con mi observacion despues de enviarla?', 'a' => 'Tu observacion ingresa al expediente del proceso. La Unidad de Ordenamiento Territorial del GORE la revisa y, segun el proceso, recibira una respuesta institucional formal.'],
                            ['q' => 'Mis datos personales son publicos?', 'a' => 'No. Tu correo, RUT y datos de contacto no son publicos. Solo el contenido de tu observacion es publico segun los plazos definidos por la Ley N°21.074. El GORE custodia tus datos personales bajo D.S. N°7/2023.'],
                            ['q' => 'Que es ClaveUnica?', 'a' => 'ClaveUnica es el sistema unico de autenticacion de identidad digital del Estado de Chile. Te permite acceder de forma segura a los servicios digitales del Estado con una sola credencial.'],
                        ] as $i => $faq)
                            <div class="accordion-item bg-transparent">
                                <h3 class="accordion-header">
                                    <button class="accordion-button {{ $i > 0 ? 'collapsed' : '' }} fw-semibold"
                                            type="button" data-bs-toggle="collapse"
                                            data-bs-target="#faq{{ $i }}">
                                        {{ $faq['q'] }}
                                    </button>
                                </h3>
                                <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                     data-bs-parent="#faq">
                                    <div class="accordion-body text-muted">
                                        {{ $faq['a'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA FINAL con aurora vibrante para cerrar la pagina con impacto --}}
    <section class="py-5 py-lg-6">
        <div class="container py-4">
            <div class="rounded-4 p-5 p-md-6 position-relative overflow-hidden gore-reveal"
                 style="background: radial-gradient(circle at 80% 20%, rgba(173,162,103,0.30) 0%, transparent 50%),
                                    radial-gradient(circle at 20% 80%, rgba(143,190,154,0.20) 0%, transparent 50%),
                                    linear-gradient(135deg, var(--gore-primary-dark) 0%, var(--gore-primary) 100%);
                        color: #fff; isolation: isolate;">
                <div class="row align-items-center g-4 position-relative" style="z-index: 1;">
                    <div class="col-lg-8">
                        <h2 class="display-6 fw-bold mb-2" style="letter-spacing: -0.02em;">
                            Listo para hacer escuchar tu voz?
                        </h2>
                        <p class="lead mb-0" style="color: rgba(255,255,255,0.85);">
                            Revisa las consultas vigentes y participa en menos de 5 minutos.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="#consultas" class="btn btn-light btn-lg fw-semibold">
                            Ver consultas vigentes <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-public-layout>
