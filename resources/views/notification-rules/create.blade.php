@use (App\Enums\RouteName)

@section ('pageTitle', __('notification_rule.create'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('notification_rule.create') }}</h1>
    </x-slot>

    @include (
        'notification-rules.form',
        [
            'notificationRule' => null,
            'action' => route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $department]),
            'formUrl' => route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_CREATE, ['department' => $department]),
            'submitLabel' => __('notification_rule.create'),
        ]
    )
</x-form-layout>
