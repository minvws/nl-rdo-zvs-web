@props([
    'type',
    'dismissible' => false,
])

<div
    role="alert"
    {{ $attributes->merge(['class' => "alert alert-$type " . ($dismissible ? 'alert-dismissible' : '')]) }}>
    {{ $slot }}
    @if ($dismissible)
        <button
            type="button"
            class="icon-only"
            data-bs-dismiss="alert"
            aria-label="{{ __('general.close') }}">
            <x-tabler-x
                fill="currentColor"
                aria-hidden="true"
                focusable="false" />
        </button>
    @endif
</div>
