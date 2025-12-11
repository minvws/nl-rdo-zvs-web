@use(Illuminate\Support\Facades\Session)

@props([
    'element' => 'div',
    'only' => null,
])

@if ($flash = Session::get('flash_notification'))
    <{{ $element }}
        class="{{ $flash->getType() }}"
        data-message-type="{{ $flash->getType() }}"
        role="status">
        <span>{{ $getNotificationTypeSpan($flash->getType()->value) }}</span>
        @if (is_scalar($flash->getMessage()))
            {{ __($flash->getMessage()) }}
        @elseif (is_array($flash->getMessage()))
            <ul>
                @foreach ($flash->getMessage() as $message)
                    <li>{{ __($message) }}</li>
                @endforeach
            </ul>
        @endif
    </{{ $element }}>
@endif
