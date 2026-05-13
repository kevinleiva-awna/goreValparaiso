<x-public-layout>
    @section('title', 'Inicio')

    {{-- HERO --}}
    <section class="gore-hero">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8 gore-fade-up">
                    <span class="gore-hero-eyebrow">
                        <i class="bi bi-megaphone"></i>
                        Region de Valparaiso &middot; Participacion Ciudadana
                    </span>
                    <h1 class="gore-hero-title">
                        Tu voz construye el ordenamiento territorial de la region.
                    </h1>
                    <p class="gore-hero-lead">
                        Plataforma oficial para participar en los procesos de consulta publica
                        sobre Instrumentos de Planificacion y Ordenamiento Territorial (IPT, PROT y ZUBC)
                        del Gobierno Regional de Valparaiso.
                    </p>

                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <a href="#consultas" class="btn btn-light btn-lg fw-semibold">
                            Ver consultas vigentes
                            <i class="bi bi-arrow-down ms-1"></i>
                        </a>
                        <a href="#como-funciona" class="btn btn-outline-light btn-lg">
                            Como funciona
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 d-none d-lg-block">
                    <div class="position-relative" style="aspect-ratio: 1; max-width: 380px; margin-left: auto;">
                        <div class="position-absolute top-0 end-0 bg-white rounded-4 p-4 shadow-lg"
                             style="width: 220px; transform: rotate(-3deg);">
                            <div class="small text-uppercase fw-semibold mb-1"
                                 style="color: var(--gore-primary); letter-spacing: 0.05em;">
                                PROT activo
                            </div>
                            <div class="fw-bold mb-2" style="color: var(--gore-ink);">
                                Plan Regional de Ordenamiento Territorial
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span><i class="bi bi-clock me-1"></i>32 dias restantes</span>
                                <span><i class="bi bi-people me-1"></i>1.247</span>
                            </div>
                        </div>
                        <div class="position-absolute bottom-0 start-0 bg-white rounded-4 p-4 shadow-lg"
                             style="width: 210px; transform: rotate(2deg);">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div class="d-inline-flex align-items-center justify-content-center"
                                     style="width: 32px; height: 32px; background: rgba(16,185,129,0.12); color: #059669; border-radius: 8px;">
                                    <i class="bi bi-check-circle-fill"></i>
                                </div>
                                <div class="fw-semibold small" style="color: var(--gore-ink);">
                                    Observacion enviada
                                </div>
                            </div>
                            <div class="small text-muted">
                                Quedo registrada con identidad verificada via ClaveUnica.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- BENEFICIOS --}}
    <section class="py-5 py-lg-6" id="beneficios">
        <div class="container py-4">
            <div class="row mb-5">
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

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="gore-feature-card">
                        <div class="gore-feature-icon"><i class="bi bi-shield-check"></i></div>
                        <h3>Identidad verificada</h3>
                        <p>Iniciar sesion con ClaveUnica o registro manual con RUT y correo institucionalmente validado.</p>
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
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-5 gap-3">
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
                <div class="row g-4">
                    @foreach ($featured as $c)
                        @php
                            $daysLeft = $c->ends_at ? max(0, floor(now()->diffInDays($c->ends_at, false))) : null;
                            $isOpen = $c->status === 'active';
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
                                <div class="d-flex justify-content-between align-items-center pt-3 mt-auto"
                                     style="border-top: 1px solid var(--gore-border);">
                                    <span class="small text-muted">
                                        @if ($isOpen && $daysLeft !== null && $daysLeft > 0)
                                            <i class="bi bi-clock me-1"></i>
                                            Cierra en {{ $daysLeft }} dias
                                        @elseif ($c->starts_at)
                                            <i class="bi bi-calendar-event me-1"></i>
                                            Inicia {{ $c->starts_at->format('d/m/Y') }}
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
    <section class="py-5 py-lg-6" id="como-funciona">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-5">
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
                    <div class="row g-3">
                        @foreach ([
                            ['n' => '01', 'title' => 'Selecciona la consulta', 'text' => 'Revisa las consultas vigentes y elige el proceso en el que quieres participar.'],
                            ['n' => '02', 'title' => 'Revisa los antecedentes', 'text' => 'Descarga los documentos tecnicos oficiales del instrumento.'],
                            ['n' => '03', 'title' => 'Identificate', 'text' => 'Inicia sesion con ClaveUnica o registrate con tus datos personales.'],
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
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <span class="gore-badge gore-badge-brand mb-3">FAQ</span>
                        <h2 class="h1 fw-bold mb-2" style="letter-spacing: -0.02em;">Preguntas frecuentes</h2>
                    </div>

                    <div class="accordion accordion-flush" id="faq">
                        @foreach ([
                            ['q' => 'Quien puede participar en las consultas?', 'a' => 'Cualquier persona natural mayor de edad puede participar identificandose via ClaveUnica o mediante registro manual con su RUT y correo electronico.'],
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

    {{-- CTA FINAL --}}
    <section class="py-5 py-lg-6">
        <div class="container py-4">
            <div class="rounded-4 p-5 p-md-6 position-relative overflow-hidden"
                 style="background: linear-gradient(135deg, var(--gore-primary-dark) 0%, var(--gore-primary) 100%); color: #fff;">
                <div class="row align-items-center g-4 position-relative">
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
