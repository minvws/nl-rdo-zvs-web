@props([
    'content' => null,
    'help_text' => false,
    'required' => false,
])

<label {{ $attributes }}>
    {{ $content }}
    @if ($required)
        <span class="label__addition">({{ __('general.required') }})</span>
    @endif

    @if ($help_text)
        <span class="form-text">{{ $help_text }}</span>
    @endif
</label>
