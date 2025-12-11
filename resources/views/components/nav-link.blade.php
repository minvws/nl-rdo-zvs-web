@props([
    'active' => false,
])

@php
    $classes = 'nav-link' . ($active ?? false ? ' active' : '');
    $ariaCurrent = $active ? 'page' : null;
@endphp

<a
    {{ $attributes->merge(['class' => $classes]) }}
    @if($ariaCurrent) aria-current="{{ $ariaCurrent }}" @endif>
    {{ $slot }}
</a>
