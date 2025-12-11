@props([
    'messages',
    'id',
])

@if ($messages)
    @foreach ((array) $messages as $message)
        <p
            id="{{ $id }}"
            class="input-error__message">
            <x-tabler-circle-x
                aria-hidden="true"
                focusable="false" />
            {{ $message }}
        </p>
    @endforeach
@endif
