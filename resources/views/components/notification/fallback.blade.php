@props([
    'notification',
    'reason' => null,
])

<div class="notification-fallback">
    <p>
        {{ $notification->type }}
    </p>
    <p>
        {{ $notification->data['title'] ?? '-' }}
    </p>
    <p><small>{{ __('general.created') }} {{ $notification->created_at->format('d-m-Y H:i') }}</small></p>
</div>
