<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="min-vh-100 d-flex flex-column">
        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white border-bottom shadow-sm">
                <div class="container py-3">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-grow-1">
            {{ $slot }}
        </main>

        <footer class="bg-white border-top mt-auto py-3">
            <div class="container small text-muted text-center">
                Gobierno Regional de Valparaiso &middot; AWNA &middot; v{{ app()->version() }}
            </div>
        </footer>
    </div>
</body>
</html>
