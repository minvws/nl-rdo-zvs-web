<x-mail::message>
    <div>
        <p>{{ __('notification_rule.mail.greeting') }}</p>
        <p>
            {{ __('notification_rule.mail.intro', ['number' => $petitionNumber]) }}
            <br />
            {{ __('notification_rule.mail.regarding', ['regarding' => $regarding]) }}
            <br />
            {{ __('notification_rule.mail.action', ['action' => $action]) }}
        </p>
        <p>{{ __('notification_rule.mail.instruction') }}</p>
        <p>{{ __('notification_rule.mail.automatic') }}</p>
        <p>{{ __('notification_rule.mail.signature') }}</p>
    </div>
</x-mail::message>
