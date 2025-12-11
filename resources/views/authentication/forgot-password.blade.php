<x-form-layout>
    <h1 class="visually-hidden">
        {{ __('authorization.forgot_password.header') }}
    </h1>

    <!-- Session Status -->
    <x-auth-session-status :status="session('status')" />

    <form
        method="post"
        action="{{ route(RouteName::FORGOT_PASSWORD_EMAIL) }}"
        class="layout-authentication">
        @csrf

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
                :value="old('email')" />
        </div>

        <div class="button-container">
            <x-primary-button>
                {{ __('general.send') }}
            </x-primary-button>
        </div>
    </form>
</x-form-layout>
