@vite(['resources/js/password-strength.js'])
<x-form-layout>
    <div class="visually-grouped">
        <form
            method="post"
            data-add-strength-meter="password"
            action="{{ route('password.reset.store') }}">
            @csrf
            <legend>{{ __('authentication.reset_password.title') }}</legend>
            <!-- Password Reset Token -->
            <input
                type="hidden"
                name="id"
                value="{{ $id }}" />
            <input
                type="hidden"
                name="token"
                value="{{ $token }}" />

            <!-- Email Address -->
            <div>
                <x-input-label
                    for="email"
                    :content="__('user.email')" />
                <x-input-error
                    id="email-error"
                    :messages="$errors->get('email')" />
                <x-text-input
                    id="email"
                    :hasError="$errors->has('email')"
                    type="email"
                    name="email"
                    aria-describedby="email-error"
                    :value="old('email', $email)"
                    disabled
                    autocomplete="username" />
            </div>

            <!-- Password -->
            <div>
                <x-input-label
                    for="password"
                    :content="__('user.password')" />

                <p>{{ __('authentication.password-strength.constraints') }}</p>

                <x-input-error
                    id="password-error"
                    :messages="$errors->get('password')" />
                <x-text-input
                    id="password"
                    :hasError="$errors->has('password')"
                    type="password"
                    name="password"
                    aria-describedby="password-error"
                    autofocus
                    autocomplete="new-password" />
                @include('authentication.auth-password-strength-meter')
            </div>

            <!-- Confirm Password -->
            <div>
                <x-input-label
                    for="password_confirmation"
                    :content="__('user.password_confirm')" />
                <x-input-error
                    id="confirmation-error"
                    :messages="$errors->get('password_confirmation')" />
                <x-text-input
                    id="password_confirmation"
                    :hasErrors="$errors->has('password_confirmation')"
                    type="password"
                    name="password_confirmation"
                    aria-describedby="confirmation-error"
                    autocomplete="new-password" />
            </div>

            <div class="button-container">
                <x-primary-button>
                    {{ __('general.save') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-form-layout>
