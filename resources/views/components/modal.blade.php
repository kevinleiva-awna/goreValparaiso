@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
])

@php
    $sizeClass = match ($maxWidth) {
        'sm' => 'modal-sm',
        'md', 'lg' => 'modal-lg',
        'xl', '2xl' => 'modal-xl',
        default => '',
    };
@endphp

<div class="modal fade {{ $show ? 'show' : '' }}"
     id="{{ $name }}"
     tabindex="-1"
     aria-hidden="{{ $show ? 'false' : 'true' }}"
     @if ($show) style="display: block;" @endif
     data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered {{ $sizeClass }}">
        <div class="modal-content">
            {{ $slot }}
        </div>
    </div>
</div>
