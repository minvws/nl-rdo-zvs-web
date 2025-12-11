@vite(['resources/js/password-strength.js'])
<section class="visually-grouped">
    <div class="spacing-0">
        <h2>
            {{ __('profile.password.title') }}
        </h2>

        <p>
            {{ __('profile.password.subtitle') }}
        </p>
    </div>

    <form
        method="post"
        data-add-strength-meter="password"
        action="{{ route(RouteName::PASSWORD_UPDATE) }}">
        @csrf
        @method('put')

        <div>
            <x-input-label
                for="update_password_current_password"
                required
                :content="__('profile.password.password_current')" />
            <x-input-error
                id="password-error"
                :messages="$errors->updatePassword->get('current_password')" />
            <x-text-input
                id="update_password_current_password"
                :hasErrors="$errors->has('current_password')"
                name="current_password"
                type="password"
                aria-describedby="password-error"
                autocomplete="current-password" />
        </div>

        <div>
            <x-input-label
                for="update_password_password"
                required
                :content="__('profile.password.password_new')" />

            <p>{{ __('authentication.password-strength.constraints') }}</p>

            <x-input-error
                id="update-error"
                :messages="$errors->updatePassword->get('password')" />
            <x-text-input
                id="update_password_password"
                :hasErrors="$errors->has('password')"
                name="password"
                type="password"
                aria-describedby="update-error"
                autocomplete="new-password" />
            @include('authentication.auth-password-strength-meter')
        </div>

        <div>
            <x-input-label
                for="update_password_password_confirmation"
                required
                :content="__('profile.password.password_confirm')" />
            <x-input-error
                id="confirmation-error"
                :messages="$errors->updatePassword->get('password_confirmation')" />
            <x-text-input
                id="update_password_password_confirmation"
                :hasErrors="$errors->has('password_confirmation')"
                name="password_confirmation"
                type="password"
                aria-describedby="confirmation-error"
                autocomplete="new-password" />
        </div>

        <div>
            <x-primary-button>{{ __('general.save') }}</x-primary-button>
        </div>
    </form>
    @if ($errors->any())
        {{ $errors }}
    @endif
</section>
