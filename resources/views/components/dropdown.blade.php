@props(['align' => 'end'])

@php
    $alignClass = $align === 'left' ? 'dropdown-menu-start' : 'dropdown-menu-end';
@endphp

<div {{ $attributes->merge(['class' => 'dropdown']) }}>
    <div data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;">
        {{ $trigger }}
    </div>

    <ul class="dropdown-menu {{ $alignClass }} shadow">
        {{ $content }}
    </ul>
</div>
