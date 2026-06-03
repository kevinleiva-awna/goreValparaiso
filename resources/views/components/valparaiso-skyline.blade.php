{{-- Skyline SVG estilizado de Valparaiso para uso decorativo en hero o
     secciones grandes. Tres planos de cerros con casas pequeñas en azul
     institucional, mar en linea horizontal al frente, sol/luna sugerido.
     Disenado para verse bien sobre fondo claro/degrade del body. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 360" fill="none"
     {{ $attributes->merge(['class' => 'gore-skyline', 'aria-hidden' => 'true']) }}>
    {{-- Cielo: gradient suave azul --}}
    <defs>
        <linearGradient id="goreSky" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#e3e9f5" stop-opacity="0"/>
            <stop offset="100%" stop-color="#c9d3eb" stop-opacity="0.5"/>
        </linearGradient>
        <linearGradient id="goreHillBack" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#3a4796"/>
            <stop offset="100%" stop-color="#1f2862"/>
        </linearGradient>
        <linearGradient id="goreHillMid" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#4a5ba8"/>
            <stop offset="100%" stop-color="#2c3475"/>
        </linearGradient>
        <linearGradient id="goreHillFront" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#5d6cb8"/>
            <stop offset="100%" stop-color="#3a4796"/>
        </linearGradient>
        <linearGradient id="goreSea" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" stop-color="#6878b5" stop-opacity="0.45"/>
            <stop offset="100%" stop-color="#3a4796" stop-opacity="0.30"/>
        </linearGradient>
    </defs>

    {{-- Cielo --}}
    <rect width="800" height="280" fill="url(#goreSky)"/>

    {{-- Sol sugerido (circulo dorado muy sutil) --}}
    <circle cx="640" cy="90" r="36" fill="#ada267" opacity="0.18"/>
    <circle cx="640" cy="90" r="22" fill="#ada267" opacity="0.25"/>

    {{-- Cerro mas lejano (fondo) --}}
    <path d="M0 200 L60 175 L140 165 L220 150 L300 160 L380 140 L460 155 L540 145 L620 160 L700 150 L800 165 L800 280 L0 280 Z"
          fill="url(#goreHillBack)" opacity="0.55"/>

    {{-- Cerro medio --}}
    <path d="M0 230 L80 210 L160 220 L240 195 L320 215 L400 200 L480 220 L560 210 L640 225 L720 215 L800 230 L800 280 L0 280 Z"
          fill="url(#goreHillMid)" opacity="0.75"/>

    {{-- Cerro frontal con casitas estilizadas. Las casas son cuadrados
         pequeños sobre la pendiente del cerro — referencia a los cerros
         de Valparaiso (Concepcion, Alegre, Bellavista, etc). --}}
    <path d="M0 260 L100 240 L200 250 L280 230 L360 245 L440 232 L520 248 L600 240 L680 252 L800 245 L800 320 L0 320 Z"
          fill="url(#goreHillFront)"/>

    {{-- Casitas: rectangulos chicos con ventanas sugeridas, distribuidas
         a lo largo del cerro frontal. --}}
    @php
        $houses = [
            ['x' => 30,  'y' => 250, 'w' => 14, 'h' => 12, 'fill' => '#e8ecf5'],
            ['x' => 60,  'y' => 254, 'w' => 12, 'h' => 10, 'fill' => '#ada267'],
            ['x' => 90,  'y' => 248, 'w' => 16, 'h' => 14, 'fill' => '#fff'],
            ['x' => 130, 'y' => 252, 'w' => 14, 'h' => 12, 'fill' => '#8fbe9a'],
            ['x' => 165, 'y' => 256, 'w' => 12, 'h' => 10, 'fill' => '#e8ecf5'],
            ['x' => 200, 'y' => 250, 'w' => 14, 'h' => 12, 'fill' => '#fff'],
            ['x' => 235, 'y' => 245, 'w' => 16, 'h' => 14, 'fill' => '#ada267'],
            ['x' => 280, 'y' => 240, 'w' => 14, 'h' => 14, 'fill' => '#e8ecf5'],
            ['x' => 320, 'y' => 248, 'w' => 12, 'h' => 12, 'fill' => '#8fbe9a'],
            ['x' => 360, 'y' => 250, 'w' => 14, 'h' => 12, 'fill' => '#fff'],
            ['x' => 400, 'y' => 246, 'w' => 16, 'h' => 14, 'fill' => '#ada267'],
            ['x' => 445, 'y' => 244, 'w' => 14, 'h' => 12, 'fill' => '#e8ecf5'],
            ['x' => 485, 'y' => 250, 'w' => 12, 'h' => 10, 'fill' => '#fff'],
            ['x' => 525, 'y' => 252, 'w' => 14, 'h' => 12, 'fill' => '#8fbe9a'],
            ['x' => 565, 'y' => 246, 'w' => 16, 'h' => 14, 'fill' => '#fff'],
            ['x' => 610, 'y' => 248, 'w' => 14, 'h' => 12, 'fill' => '#ada267'],
            ['x' => 650, 'y' => 252, 'w' => 12, 'h' => 10, 'fill' => '#e8ecf5'],
            ['x' => 690, 'y' => 254, 'w' => 14, 'h' => 12, 'fill' => '#fff'],
            ['x' => 730, 'y' => 250, 'w' => 12, 'h' => 10, 'fill' => '#8fbe9a'],
            ['x' => 765, 'y' => 252, 'w' => 14, 'h' => 12, 'fill' => '#ada267'],
        ];
    @endphp
    @foreach ($houses as $h)
        <rect x="{{ $h['x'] }}" y="{{ $h['y'] }}" width="{{ $h['w'] }}" height="{{ $h['h'] }}"
              fill="{{ $h['fill'] }}" opacity="0.92"/>
        {{-- Ventana sugerida (cuadrado azul oscuro mas pequeño) --}}
        <rect x="{{ $h['x'] + 2 }}" y="{{ $h['y'] + 2 }}" width="3" height="3" fill="#1f2862" opacity="0.6"/>
    @endforeach

    {{-- Mar al frente (lineas horizontales sugiriendo agua) --}}
    <rect x="0" y="280" width="800" height="80" fill="url(#goreSea)"/>
    <line x1="0" y1="295" x2="800" y2="295" stroke="#fff" stroke-width="0.6" opacity="0.4"/>
    <line x1="0" y1="305" x2="800" y2="305" stroke="#fff" stroke-width="0.5" opacity="0.3"/>
    <line x1="0" y1="315" x2="800" y2="315" stroke="#fff" stroke-width="0.4" opacity="0.25"/>

    {{-- Velero pequeño en el mar (referencia al puerto historico) --}}
    <g transform="translate(420, 282)">
        <path d="M0 8 L14 8 L11 2 Z" fill="#fff" opacity="0.85"/>
        <line x1="7" y1="8" x2="7" y2="0" stroke="#fff" stroke-width="0.6" opacity="0.85"/>
        <path d="M-2 10 L16 10 L14 8 L0 8 Z" fill="#fff" opacity="0.85"/>
    </g>
</svg>
