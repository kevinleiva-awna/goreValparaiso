<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--bs-primary);">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                Gobierno Regional de Valparaiso
            </a>
            <div class="d-flex align-items-center gap-3">
                <span class="navbar-text text-white-50 small d-none d-md-inline">
                    Plataforma de Participacion Ciudadana
                </span>
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-shield-lock me-1"></i> Backoffice
                </a>
            </div>
        </div>
    </nav>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <h1 class="h2 mb-3">Procesos Participativos Reglados</h1>
                        <p class="text-muted mb-4">
                            Consulta publica de Instrumentos de Planificacion y Ordenamiento Territorial
                        </p>
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle me-2"></i>
                            Plataforma en construccion. Lanzamiento previsto para junio de 2026.
                        </div>
                    </div>
                </div>

                <div class="row mt-4 g-3 text-center small text-muted">
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm">
                            Laravel {{ app()->version() }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm">
                            PHP {{ PHP_VERSION }}
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white rounded shadow-sm">
                            Locale: {{ app()->getLocale() }} - TZ: {{ config('app.timezone') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="container py-4 text-center text-muted small">
        Gobierno Regional de Valparaiso &middot; AWNA
    </footer>
</body>
</html>
