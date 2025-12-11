@props([
    'bg_color',
])

<span
    class="tag tag--substatus"
    data-status-color="{{ $bg_color }}">
    {{ $slot }}
</span>
