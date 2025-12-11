@props([
    'type' => 'submit',
])

<button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => 'button']) }}>
    {{ $slot }}
</button>
