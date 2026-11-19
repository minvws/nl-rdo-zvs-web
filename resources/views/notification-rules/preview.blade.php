@use (App\Services\NotificationRule\NotificationRuleText)

<h2>{{ __('notification_rule.preview') }}</h2>
<div class="visually-grouped">
    @if ($notificationRule === null)
        <p>{{ __('notification_rule.preview_incomplete') }}</p>
    @else
        <p>{{ __('notification_rule.mail.greeting') }}</p>
        <p>
            {{ __('notification_rule.mail.intro', ['number' => __('notification_rule.mail.number_placeholder')]) }}
            <br />
            {{ __('notification_rule.mail.regarding', ['regarding' => $eventTypes[$notificationRule->event_type->value]]) }}
            <br />
            {{ __('notification_rule.mail.action', ['action' => NotificationRuleText::action($notificationRule)]) }}
        </p>
        <p>{{ __('notification_rule.mail.instruction') }}</p>
        <p>{{ __('notification_rule.mail.automatic') }}</p>
        <p>{{ __('notification_rule.mail.signature') }}</p>
    @endif
</div>
