@props([
    'disabled' => false,
    'hasError' => false,
])

<input
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge(['class' => 'form-control ' . ($hasError ? 'input-error' : '')]) !!}
    @if($hasError) aria-invalid="true"@endif />
