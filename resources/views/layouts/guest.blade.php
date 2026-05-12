<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Backoffice &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="gore-auth-shell">
        {{-- Panel izquierdo: identidad institucional (desktop) --}}
        <aside class="gore-auth-aside">
            <div class="d-flex justify-content-between align-items-start">
                <x-application-logo background="dark" />
                <a href="{{ url('/') }}" class="d-inline-flex align-items-center text-white text-decoration-none">
                    <i class="bi bi-arrow-left me-2"></i>
                    <span class="small">Sitio publico</span>
                </a>
            </div>

            <div>
                <div class="d-inline-flex align-items-center justify-content-center mb-4"
                     style="width: 64px; height: 64px; background: rgba(255,255,255,0.10); border: 1px solid rgba(255,255,255,0.18); border-radius: 16px;">
                    <i class="bi bi-shield-lock-fill" style="font-size: 1.75rem; color: #fff;"></i>
                </div>
                <h2 class="display-6 fw-bold mb-3" style="letter-spacing: -0.02em;">
                    Backoffice del GORE
                </h2>
                <p class="lead mb-0" style="color: rgba(255,255,255,0.85); max-width: 38ch;">
                    Acceso restringido a funcionarios autorizados de la Region de Valparaiso
                    para administrar procesos de consulta publica.
                </p>
            </div>

            <div class="small" style="color: rgba(255,255,255,0.6);">
                <i class="bi bi-shield-check me-1"></i>
                Conexion cifrada bajo D.S. N&deg;7/2023
            </div>
        </aside>

        {{-- Panel derecho: formulario --}}
        <section class="gore-auth-main">
            <div class="gore-auth-card">
                <div class="text-center mb-4">
                    <a href="{{ url('/') }}" class="text-decoration-none">
                        <x-application-logo class="justify-content-center" />
                    </a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        {{ $slot }}
                    </div>
                </div>

                <p class="text-center text-muted small mt-4 mb-0">
                    &copy; {{ date('Y') }} Gobierno Regional de Valparaiso
                </p>
            </div>
        </section>
    </div>
</body>
</html>
