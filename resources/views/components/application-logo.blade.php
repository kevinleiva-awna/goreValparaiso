{{-- Logo institucional GORE Valparaiso.

     Archivos disponibles en public/img/brand/:
       - Logo_A.png  Banner completo a COLOR (escudo + Gobierno Regional + #Valparaiso Region de Derechos).
                     Para usar sobre fondos claros (navbar, hero claro).
       - Logo_B.png  Mismo banner pero VERSION CLARA sobre fondo NEGRO.
       - Logo_C_Core.png  Banner triple (Gobierno Regional + Slogan + CORE) a color.
       - Logo_D_CORE.png  Banner triple en azul monocromo.
       - Logo_E_core.png  Banner triple en blanco (para fondos oscuros).
       - escudo_gore_b.png  Banner sobre fondo negro (idem Logo_B).

     Props:
       - background: 'light' (default, sobre fondo claro) | 'dark' (sobre fondo oscuro)
       - variant:    'full' (default, banner doble: escudo + slogan)
                     | 'compact' (alto reducido para navbar mobile)
                     | 'triple'  (escudo + slogan + CORE)
       - height:     altura en pixeles (default 44 para full, 36 para compact, 52 para triple).
                     Override explicito si necesitas otra cosa.
--}}
@props([
    'background' => 'light',
    'variant' => 'full',
    'height' => null,
])

@php
    $isDark = $background === 'dark';

    // Mapa de archivos por (variant, background). El primer archivo existente
    // gana. Si NINGUNO existe, caemos al placeholder al final.
    $candidates = match ($variant) {
        'triple'  => $isDark ? ['Logo_E_core.png'] : ['Logo_C_Core.png', 'Logo_D_CORE.png'],
        default   => $isDark ? ['Logo_B.png', 'escudo_gore_b.png'] : ['Logo_A.png'],
    };

    $imagePath = null;
    foreach ($candidates as $file) {
        if (file_exists(public_path('img/brand/' . $file))) {
            $imagePath = asset('img/brand/' . $file);
            break;
        }
    }

    $heightPx = $height ?: match ($variant) {
        'compact' => 36,
        'triple'  => 52,
        default   => 44,
    };
@endphp

<span {{ $attributes->merge(['class' => 'gore-logo d-inline-flex align-items-center']) }}>
    @if ($imagePath)
        <img src="{{ $imagePath }}"
             alt="Gobierno Regional de la Region de Valparaiso"
             class="gore-logo-image"
             style="height: {{ $heightPx }}px; width: auto; object-fit: contain; display: block;">
    @else
        {{-- Fallback solo si NO existe ningun archivo (no deberia pasar en
             produccion; queda por defensa). --}}
        <span class="d-inline-flex align-items-center justify-content-center me-2"
              style="width: 36px; height: 36px; background: {{ $isDark ? '#fff' : 'var(--gore-primary)' }}; color: {{ $isDark ? 'var(--gore-primary)' : '#fff' }}; border-radius: 8px;">
            <i class="bi bi-shield-fill" style="font-size: 1.125rem;"></i>
        </span>
        @if ($variant !== 'compact')
            <span style="line-height: 1.15;">
                <span style="color: {{ $isDark ? '#fff' : 'var(--gore-ink)' }}; font-weight: 600; font-size: 0.875rem; display: block;">
                    Gobierno Regional
                </span>
                <span style="color: {{ $isDark ? 'rgba(255,255,255,0.72)' : 'var(--gore-ink-soft)' }}; font-weight: 500; font-size: 0.75rem;">
                    Regi&oacute;n de Valpara&iacute;so
                </span>
            </span>
        @endif
    @endif
</span>
