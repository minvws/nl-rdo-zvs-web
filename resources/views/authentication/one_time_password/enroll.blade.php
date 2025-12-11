<x-form-layout>
    <x-slot name="header">
        <h1>
            {{ __('profile.title') }}
        </h1>
    </x-slot>
    <section class="visually-grouped">
        <div class="spacing-0">
            <h2>
                {{ __('profile.one_time_password.title') }}
            </h2>

            <p>
                @if (! Otp::hasOtpVerified($user))
                    {{ __('profile.one_time_password.subtitle') }}
                @else
                    {{ __('profile.one_time_password.already_enrolled') }}
                @endif
            </p>
        </div>
        @if (! Otp::hasOtpEnabled($user))
            <form
                method="post"
                action="{{ route('one-time-password.enable') }}">
                @csrf
                <x-primary-button>{{ __('profile.one_time_password.enable') }}</x-primary-button>
            </form>
        @elseif (! Otp::hasOtpVerified($user))
            <form
                method="post"
                action="{{ route('one-time-password.confirm') }}">
                @csrf

                <fieldset>
                    <legend>QR-code</legend>
                    <div
                        role="img"
                        aria-label="QR-code">
                        {!! Otp::generateQRCodeInline($user) !!}
                    </div>

                    <div>
                        <x-input-label
                            for="update_otp_otp_confirmation"
                            :content="__('profile.one_time_password.confirm')" />
                        <x-input-error
                            id="confirmation-error"
                            :messages="$errors->get('otp_confirmation')" />
                        <x-text-input
                            id="update_otp_otp_confirmation"
                            :hasErrors="$errors->has('otp_confirmation')"
                            name="otp_confirmation"
                            aria-describedby="confirmation-error" />
                    </div>
                </fieldset>

                <x-primary-button>{{ __('general.save') }}</x-primary-button>
            </form>
        @endif
    </section>
</x-form-layout>
