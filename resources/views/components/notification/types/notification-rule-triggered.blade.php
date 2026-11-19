@props ([
    'notification',
])

<div class="notification-content">
    <h2>{{ $notification->data['title'] }}</h2>
    <p>
        {{ __('notification_rule.mail.regarding', ['regarding' => $notification->data['regarding']]) }}
        <br />
        {{ __('notification_rule.mail.action', ['action' => $notification->data['action']]) }}
    </p>
    <p>
        <a href="{{ $notification->data['url'] }}">
            {{ __('petition.details') }}
            <x-tabler-chevron-right
                aria-hidden="true"
                focusable="false" />
        </a>
    </p>
    <p><small>{{ __('general.created') }} {{ $notification->created_at->format('d-m-Y H:i') }}</small></p>
</div>
