@props([
    'tag',
])

<span class="tag tag--color-{{ $tag->color->value }}">{{ $tag->name }}</span>
