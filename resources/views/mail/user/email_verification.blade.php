<x-mail::message>
    <div>
        <p>
            {{ __('user.mail.email_verification.text') }}
            <br />
            <x-mail::button :url="$link">{{ __('user.mail.email_verification.button_text') }}</x-mail::button>
        </p>
    </div>
</x-mail::message>
