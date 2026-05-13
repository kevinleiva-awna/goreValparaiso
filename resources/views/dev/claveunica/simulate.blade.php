<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mock ClaveUnica - Solo para desarrollo</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
    <style>
        body {
            background: linear-gradient(135deg, #0d1247 0%, #151c68 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
            padding: 1rem;
        }
        .mock-banner {
            background: #fbbf24;
            color: #78350f;
            padding: 0.5rem 1rem;
            text-align: center;
            font-size: 0.8125rem;
            font-weight: 600;
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
        }
        .mock-card {
            background: white;
            border-radius: 16px;
            max-width: 480px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
        }
        .mock-header {
            background: white;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .mock-header .logo {
            width: 40px; height: 40px;
            background: #003a70; color: white;
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700;
        }
        .mock-body { padding: 2rem; }
        .mock-title { font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.5rem; }
        .mock-subtitle { color: #6b7280; font-size: 0.875rem; margin-bottom: 1.5rem; }
    </style>
</head>
<body>
    <div class="mock-banner">
        <i class="bi bi-cone-striped"></i>
        Simulador local de ClaveUnica - SOLO DESARROLLO. No usar en produccion.
    </div>

    <div class="mock-card">
        <div class="mock-header">
            <div class="logo">CU</div>
            <div>
                <div style="font-weight: 700; color: #111827;">ClaveUnica</div>
                <div style="font-size: 0.75rem; color: #6b7280;">Gobierno de Chile (simulado)</div>
            </div>
        </div>

        <div class="mock-body">
            <h1 class="mock-title">Ingresar con ClaveUnica</h1>
            <p class="mock-subtitle">
                Esta es la pagina que veria un ciudadano al ser redirigido al portal oficial.
                Completa los datos como si fueras la persona autenticandose.
            </p>

            @if ($errors->any())
                <div class="alert alert-danger small">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('mock.claveunica.complete') }}">
                @csrf
                <input type="hidden" name="state" value="{{ $state }}">
                <input type="hidden" name="redirect_uri" value="{{ $redirectUri }}">

                <div class="mb-3">
                    <label class="form-label small fw-semibold">RUN *</label>
                    <input type="text" name="run" value="{{ old('run', '22.456.789-8') }}"
                           class="form-control" required placeholder="12.345.678-9">
                    <div class="form-text small">Cualquier RUT valido con DV correcto.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Nombres *</label>
                    <input type="text" name="name" value="{{ old('name', 'Maria Jose') }}"
                           class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Apellidos</label>
                    <input type="text" name="last_name" value="{{ old('last_name', 'Gonzalez Perez') }}"
                           class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Correo (opcional)</label>
                    <input type="email" name="email" value="{{ old('email', 'mj@ejemplo.cl') }}"
                           class="form-control">
                    <div class="form-text small">ClaveUnica real puede o no entregar el correo.</div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary btn-lg" style="background: #003a70; border-color: #003a70;">
                        Autorizar e ingresar
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
