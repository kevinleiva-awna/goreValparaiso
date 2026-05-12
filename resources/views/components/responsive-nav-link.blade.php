@props(['active'])

@php
    $classes = ($active ?? false) ? 'nav-link active fw-semibold' : 'nav-link';
@endphp

<a {{ $attributes->merge(['class' => $classes . ' d-block px-3 py-2']) }}>
    {{ $slot }}
</a>
