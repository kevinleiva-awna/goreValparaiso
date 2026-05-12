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

        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-file-earmark-text text-primary me-2"></i>Consultas
                        </h5>
                        <p class="card-text text-muted small">
                            Gestion de procesos de consulta publica.
                        </p>
                        <span class="text-muted small">Disponible en Etapa 3 (D9-D14)</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-chat-square-text text-primary me-2"></i>Observaciones
                        </h5>
                        <p class="card-text text-muted small">
                            Listado, filtros y exportacion de observaciones ciudadanas.
                        </p>
                        <span class="text-muted small">Disponible en Etapa 3 (D12)</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-people text-primary me-2"></i>Usuarios
                        </h5>
                        <p class="card-text text-muted small">
                            Gestion de funcionarios y permisos.
                        </p>
                        <span class="text-muted small">Disponible en Etapa 3 (D11)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
