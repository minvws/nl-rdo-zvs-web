<x-form-layout>
    <form
        method="post"
        action="{{ route('one-time-password.authenticate') }}"
        class="layout-authentication">
        @csrf

        <div>
            <x-input-label
                for="code"
                :content="__('authentication.one_time_password.code')" />
            <x-input-error
                id="code-error"
                :messages="$errors->get('code')" />
            <x-text-input
                id="code"
                :hasError="$errors->has('code')"
                type="text"
                name="code"
                aria-describedby="code-error"
                autofocus />
        </div>

        <div>
            <x-primary-button>
                {{ __('general.send') }}
            </x-primary-button>
        </div>
    </form>
</x-form-layout>
