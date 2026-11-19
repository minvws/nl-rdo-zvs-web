@use (App\Enums\RouteName)

@section ('pageTitle', __('notification_rule.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('notification_rule.edit') }}</h1>
    </x-slot>

    @include (
        'notification-rules.form',
        [
            'action' => route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT, [
                'department' => $department,
                'notificationRule' => $notificationRule->id,
            ]),
            'formUrl' => route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_EDIT, [
                'department' => $department,
                'notificationRule' => $notificationRule->id,
            ]),
            'submitLabel' => __('general.save'),
        ]
    )
</x-form-layout>
