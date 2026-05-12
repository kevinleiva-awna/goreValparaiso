{{-- Logo institucional GORE Valparaiso.

     Archivos esperados en public/img/brand/:
       - escudo_gore_b.png  (logo completo sobre fondo NEGRO)
       - escudo_gore_w.png  (logo completo sobre fondo BLANCO, pendiente de entrega)

     Props:
       - background: 'light' (default, sobre fondo claro) | 'dark' (sobre fondo oscuro)
       - variant:    'compact' (solo escudo) | 'full' (escudo + texto)
--}}
@props([
    'background' => 'light',
    'variant' => 'full',
])

@php
    $darkLogo = public_path('img/brand/escudo_gore_b.png');
    $lightLogo = public_path('img/brand/escudo_gore_w.png');
    $hasDark = file_exists($darkLogo);
    $hasLight = file_exists($lightLogo);
    $useDark = $background === 'dark';
    $imagePath = $useDark
        ? ($hasDark ? asset('img/brand/escudo_gore_b.png') : null)
        : ($hasLight ? asset('img/brand/escudo_gore_w.png') : null);
@endphp

<span {{ $attributes->merge(['class' => 'gore-logo d-inline-flex align-items-center']) }}>
    @if ($imagePath)
        {{-- Tenemos imagen para este fondo: la usamos directamente --}}
        <img src="{{ $imagePath }}"
             alt="Gobierno Regional de Valparaiso"
             class="gore-logo-image"
             style="height: {{ $variant === 'compact' ? '36px' : '44px' }}; width: auto; object-fit: contain;">
    @else
        {{-- Fallback: placeholder de escudo + texto.
             Esto se ve cuando no existe el archivo correspondiente al fondo. --}}
        <span class="d-inline-flex align-items-center justify-content-center me-2"
              style="width: 36px; height: 36px; background: {{ $useDark ? '#fff' : 'var(--gore-primary)' }}; color: {{ $useDark ? 'var(--gore-primary)' : '#fff' }}; border-radius: 8px;">
            <i class="bi bi-shield-fill" style="font-size: 1.125rem;"></i>
        </span>

        @if ($variant !== 'compact')
            <span class="navbar-brand-text">
                <span style="color: {{ $useDark ? '#fff' : 'var(--gore-ink)' }};">Gobierno Regional</span>
                <span class="navbar-brand-text-secondary"
                      style="color: {{ $useDark ? 'rgba(255,255,255,0.7)' : 'var(--gore-ink-soft)' }};">
                    Region de Valparaiso
                </span>
            </span>
        @endif
    @endif
</span>
