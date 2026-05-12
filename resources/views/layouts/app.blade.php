<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Backoffice') &middot; {{ config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/scss/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="min-vh-100 d-flex flex-column">
        @include('layouts.navigation')

        @isset($header)
            <header class="bg-white border-bottom" style="border-color: var(--gore-border) !important;">
                <div class="container py-4">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <main class="flex-grow-1">
            {{ $slot }}
        </main>

        <footer class="border-top py-3 mt-auto bg-white"
                style="border-color: var(--gore-border) !important;">
            <div class="container small d-flex justify-content-between flex-wrap gap-2"
                 style="color: var(--gore-ink-soft);">
                <span>Gobierno Regional de Valparaiso &middot; Backoffice</span>
                <span>Laravel v{{ app()->version() }} &middot; AWNA</span>
            </div>
        </footer>
    </div>
</body>
</html>
