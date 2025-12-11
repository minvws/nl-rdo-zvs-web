@use(App\Enums\RouteName)

@section('pageTitle', __('authentication.login'))

<x-form-layout>
    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form
        method="post"
        action="{{ route(RouteName::LOGIN) }}"
        class="layout-authentication">
        @csrf

        <fieldset>
            <legend class="visually-hidden">{{ __('authentication.data') }}</legend>
            @if ($errors->has('authentication'))
                <x-notification type="danger">
                    {!! $errors->get('authentication')[0] !!}
                </x-notification>
            @endif

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
                    :value="old('email')"
                    autofocus
                    autocomplete="username" />
            </div>

            <div>
                <x-input-label
                    for="password"
                    :content="__('user.password')" />
                <x-input-error
                    id="password-error"
                    :messages="$errors->get('password')" />
                <x-text-input
                    id="password"
                    :hasError="$errors->has('password')"
                    type="password"
                    name="password"
                    aria-describedby="password-error"
                    autocomplete="current-password" />
            </div>

            <div class="checkbox">
                <input
                    type="checkbox"
                    id="remember"
                    name="remember"
                    value="1"
                    :checked="false" />
                <x-input-label
                    for="remember"
                    :content="__('user.remember_me')" />
            </div>
        </fieldset>

        <div>
            <a href="{{ route(RouteName::FORGOT_PASSWORD_REQUEST) }}">
                {{ __('authentication.forgot_password.title') }}
            </a>

            <x-primary-button>
                {{ __('authentication.login') }}
            </x-primary-button>
        </div>
    </form>
</x-form-layout>
