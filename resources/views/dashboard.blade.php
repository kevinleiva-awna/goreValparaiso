@php
    // Escala del grafico diario. El maximo define el 100% de altura; con todos
    // los dias en cero se evita la division por cero dejando el maximo en 1.
    $maxDia = max(1, $porDia->max('total'));
    $totalVentana = $porDia->sum('total');
    $maxProceso = max(1, $porProceso->max('observations_count') ?? 1);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0">Dashboard</h1>
            <span class="badge bg-secondary text-uppercase">{{ Auth::user()->role }}</span>
        </div>
    </x-slot>

    <div class="container py-4">
        <div class="alert alert-info d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2"></i>
            Sesion iniciada como <strong class="mx-1">{{ Auth::user()->name }} {{ Auth::user()->last_name }}</strong>
            ({{ Auth::user()->email }})
        </div>

        {{-- Indicadores --}}
        <div class="row g-3">
            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Observaciones recibidas</div>
                        <div class="display-6 fw-semibold lh-1 mt-2">{{ number_format($total, 0, ',', '.') }}</div>
                        <div class="text-muted small mt-2">Total historico, sin contar archivadas</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Pendientes de respuesta</div>
                        <div class="display-6 fw-semibold lh-1 mt-2 {{ $sinRespuesta > 0 ? 'text-warning-emphasis' : '' }}">
                            {{ number_format($sinRespuesta, 0, ',', '.') }}
                        </div>
                        <div class="text-muted small mt-2">Sin respuesta institucional emitida</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Procesos abiertos</div>
                        <div class="display-6 fw-semibold lh-1 mt-2">{{ $procesosActivos }}</div>
                        <div class="text-muted small mt-2">Admiten participacion hoy</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase">Ultimos {{ $dias }} dias</div>
                        <div class="display-6 fw-semibold lh-1 mt-2">{{ number_format($totalVentana, 0, ',', '.') }}</div>
                        <div class="text-muted small mt-2">Observaciones en la ventana</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Observaciones por dia --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5 mb-1">Observaciones por dia</h2>
                <p class="text-muted small mb-4">Ultimos {{ $dias }} dias.</p>

                @if ($totalVentana === 0)
                    <p class="text-muted mb-0">Sin observaciones en los ultimos {{ $dias }} dias.</p>
                @else
                    {{-- El grafico es decorativo para lectores de pantalla: los mismos
                         datos van completos en la tabla oculta de mas abajo. --}}
                    <div class="d-flex align-items-end gap-1" style="height: 160px;" aria-hidden="true">
                        @foreach ($porDia as $punto)
                            <div class="flex-fill d-flex flex-column justify-content-end h-100"
                                 title="{{ $punto['fecha']->translatedFormat('d \d\e F') }}: {{ $punto['total'] }}">
                                <div class="rounded-top {{ $punto['total'] > 0 ? 'bg-primary' : 'bg-body-secondary' }}"
                                     style="height: {{ $punto['total'] > 0 ? max(4, round($punto['total'] / $maxDia * 100)) : 2 }}%;"></div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between text-muted small mt-2">
                        <span>{{ $porDia->first()['fecha']->translatedFormat('d M') }}</span>
                        <span>Maximo diario: {{ $maxDia }}</span>
                        <span>{{ $porDia->last()['fecha']->translatedFormat('d M') }}</span>
                    </div>

                    <table class="visually-hidden">
                        <caption>Observaciones recibidas por dia en los ultimos {{ $dias }} dias</caption>
                        <thead>
                            <tr><th scope="col">Fecha</th><th scope="col">Observaciones</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($porDia as $punto)
                                <tr>
                                    <td>{{ $punto['fecha']->translatedFormat('d \d\e F \d\e Y') }}</td>
                                    <td>{{ $punto['total'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>

        {{-- Observaciones por proceso --}}
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h2 class="h5 mb-1">Observaciones por proceso</h2>
                <p class="text-muted small mb-4">
                    Cada fila abre el listado filtrado por ese proceso.
                </p>

                @if ($porProceso->isEmpty())
                    <p class="text-muted mb-0">Todavia no se reciben observaciones.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th scope="col">Proceso</th>
                                    <th scope="col" class="d-none d-md-table-cell">Distribucion</th>
                                    <th scope="col" class="text-end">Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($porProceso as $proceso)
                                    <tr>
                                        <td>
                                            <a href="{{ route('admin.observations.index', ['consultation_id' => $proceso->id]) }}"
                                               class="text-decoration-none">
                                                {{ $proceso->title }}
                                            </a>
                                            @if ($proceso->trashed())
                                                <span class="badge bg-secondary ms-1">Archivado</span>
                                            @endif
                                        </td>
                                        <td class="d-none d-md-table-cell" aria-hidden="true">
                                            <div class="progress" style="height: 8px;">
                                                <div class="progress-bar"
                                                     style="width: {{ round($proceso->observations_count / $maxProceso * 100) }}%;"></div>
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            {{ number_format($proceso->observations_count, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Accesos a los modulos --}}
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <a href="{{ route('admin.consultations.index') }}"
                   class="card border-0 shadow-sm h-100 text-decoration-none text-reset">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-file-earmark-text text-primary me-2"></i>Consultas
                        </h5>
                        <p class="card-text text-muted small">
                            Gestion de procesos de consulta publica.
                        </p>
                        <span class="link-primary small">Ir a consultas <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="{{ route('admin.observations.index') }}"
                   class="card border-0 shadow-sm h-100 text-decoration-none text-reset">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-chat-square-text text-primary me-2"></i>Observaciones
                        </h5>
                        <p class="card-text text-muted small">
                            Listado, filtros y exportacion de observaciones ciudadanas.
                        </p>
                        <span class="link-primary small">Ir a observaciones <i class="bi bi-arrow-right"></i></span>
                    </div>
                </a>
            </div>
            @if(Auth::user()->isSuperAdmin())
                <div class="col-md-4">
                    <a href="{{ route('admin.users.index') }}"
                       class="card border-0 shadow-sm h-100 text-decoration-none text-reset">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="bi bi-people text-primary me-2"></i>Usuarios
                            </h5>
                            <p class="card-text text-muted small">
                                Gestion de funcionarios y permisos.
                            </p>
                            <span class="link-primary small">Ir a usuarios <i class="bi bi-arrow-right"></i></span>
                        </div>
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
