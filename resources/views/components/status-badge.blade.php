@props([
    'badge_type',
])

<span class="tag tag--{{ $badge_type }}">
    {{ $slot }}
</span>
