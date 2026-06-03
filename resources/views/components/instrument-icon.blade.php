{{-- Icono distintivo por tipo de instrumento territorial.
     - IPT: grid de planificacion (Instrumento de Planificacion Territorial)
     - PROT: territorio regional (Plan Regional de Ordenamiento)
     - ZUBC: linea costera (Zonificacion del Borde Costero)
     - OTRO: pin generico
     Pensado para usarse dentro de cards de consulta y en thumbnails.
--}}
@props(['type' => 'OTRO', 'size' => 56])

@php
    $type = strtoupper($type);
@endphp

<span class="d-inline-flex align-items-center justify-content-center gore-instrument-icon"
      style="width: {{ $size }}px; height: {{ $size }}px;">
    @switch($type)
        @case('IPT')
            {{-- Grid de planificacion urbana: cuadricula con un highlight central. --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"
                 width="{{ $size }}" height="{{ $size }}" aria-hidden="true">
                <rect x="4" y="4" width="40" height="40" rx="4" fill="rgba(31,40,98,0.08)"/>
                <g stroke="#1f2862" stroke-width="1.4" stroke-linecap="square">
                    <line x1="12" y1="12" x2="36" y2="12"/>
                    <line x1="12" y1="20" x2="36" y2="20"/>
                    <line x1="12" y1="28" x2="36" y2="28"/>
                    <line x1="12" y1="36" x2="36" y2="36"/>
                    <line x1="12" y1="12" x2="12" y2="36"/>
                    <line x1="20" y1="12" x2="20" y2="36"/>
                    <line x1="28" y1="12" x2="28" y2="36"/>
                    <line x1="36" y1="12" x2="36" y2="36"/>
                </g>
                <rect x="20" y="20" width="8" height="8" fill="#ada267"/>
            </svg>
            @break

        @case('PROT')
            {{-- Mapa de region: contorno con pin central. --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"
                 width="{{ $size }}" height="{{ $size }}" aria-hidden="true">
                <rect x="4" y="4" width="40" height="40" rx="4" fill="rgba(143,190,154,0.18)"/>
                <path d="M10 14 L18 10 L26 14 L34 10 L38 14 L38 34 L30 38 L22 34 L14 38 L10 34 Z"
                      fill="none" stroke="#1f2862" stroke-width="1.4" stroke-linejoin="round"/>
                <line x1="18" y1="10" x2="18" y2="34" stroke="#1f2862" stroke-width="0.9" opacity="0.4"/>
                <line x1="26" y1="14" x2="26" y2="38" stroke="#1f2862" stroke-width="0.9" opacity="0.4"/>
                <circle cx="24" cy="24" r="3" fill="#ada267"/>
                <circle cx="24" cy="24" r="6" fill="none" stroke="#ada267" stroke-width="1" opacity="0.5"/>
            </svg>
            @break

        @case('ZUBC')
            {{-- Linea costera: olas + linea de costa con marca de zona. --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"
                 width="{{ $size }}" height="{{ $size }}" aria-hidden="true">
                <rect x="4" y="4" width="40" height="40" rx="4" fill="rgba(74,144,226,0.12)"/>
                {{-- Tierra (medio circulo arriba) --}}
                <path d="M4 4 L44 4 L44 22 Q34 18 24 22 Q14 26 4 22 Z" fill="rgba(173,162,103,0.4)"/>
                {{-- Linea de costa --}}
                <path d="M4 22 Q14 26 24 22 Q34 18 44 22" stroke="#1f2862" stroke-width="1.6" fill="none"/>
                {{-- Olas --}}
                <path d="M6 30 Q12 27 18 30 T30 30 T42 30" stroke="#4a90e2" stroke-width="1.2" fill="none" opacity="0.7"/>
                <path d="M6 36 Q12 33 18 36 T30 36 T42 36" stroke="#4a90e2" stroke-width="1.2" fill="none" opacity="0.5"/>
                {{-- Pin de zona --}}
                <circle cx="30" cy="14" r="2.5" fill="#1f2862"/>
            </svg>
            @break

        @default
            {{-- Generico: pin de proceso --}}
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" fill="none"
                 width="{{ $size }}" height="{{ $size }}" aria-hidden="true">
                <rect x="4" y="4" width="40" height="40" rx="4" fill="rgba(31,40,98,0.08)"/>
                <circle cx="24" cy="22" r="6" fill="none" stroke="#1f2862" stroke-width="1.6"/>
                <path d="M24 28 L24 38" stroke="#1f2862" stroke-width="1.6"/>
                <circle cx="24" cy="22" r="2" fill="#ada267"/>
            </svg>
    @endswitch
</span>
