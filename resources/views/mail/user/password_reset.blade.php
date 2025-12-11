<x-mail::message>
    <div>
        <p>
            {{ __('user.mail.password_reset.text') }}
            <br />
            <x-mail::button :url="$link">{{ __('user.mail.password_reset.button_text') }}</x-mail::button>
        </p>
    </div>
</x-mail::message>
