@props([
    'status',
])

@if ($status)
    <p
        {{ $attributes->merge(['class' => 'confirmation']) }}
        role="group"
        aria-label="{{ __('general.confirmation') }}">
        <span>{{ ucfirst(__('general.confirmation')) }}:</span>
        {{ $status }}
    </p>
@endif
