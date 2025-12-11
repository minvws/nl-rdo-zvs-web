@section('pageTitle', __('profile.edit'))
<x-form-layout>
    <x-slot name="header">
        <h1>
            {{ __('profile.title') }}
        </h1>
    </x-slot>

    @include('profile.partials.update-profile-information-form')
    @include('profile.partials.update-password-form')

    @if (Otp::hasOtpEnabled($user) && Otp::hasOtpVerified($user))
        <section class="visually-grouped">
            <div class="spacing-0">
                <h2>
                    {{ __('profile.one_time_password.title') }}
                </h2>
            </div>
            <form
                method="post"
                action="{{ route('profile.otp.disable') }}">
                @csrf
                <x-primary-button>{{ __('profile.one_time_password.reset') }}</x-primary-button>
            </form>
        </section>
    @endif
</x-form-layout>
