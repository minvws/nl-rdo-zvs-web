@props([
    'notification',
])

<div class="notification-content">
    <h2>{{ $notification->data['title'] }}</h2>
    <p>
        {{ $notification->data['description'] }}
        @if (isset($notification->data['url']))
            <a href="{{ $notification->data['url'] }}">
                {{ __('petition.details') }}
                <x-tabler-chevron-right
                    aria-hidden="true"
                    focusable="false" />
            </a>
        @endif
    </p>
    <p><small>{{ __('general.created') }} {{ $notification->created_at->format('d-m-Y H:i') }}</small></p>
</div>
