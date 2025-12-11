<x-form-layout>
    <h1>
        {{ __('authentication.verify_email.title') }}
    </h1>

    @if (session('status') == 'verification-link-sent')
        <div>
            {{ __('authentication.verify_email.verification_sent') }}
        </div>
    @endif

    <div>
        <form
            method="post"
            action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('authentication.verify_email.resend') }}
                </x-primary-button>
            </div>
        </form>

        <form
            method="post"
            action="{{ route('logout') }}">
            @csrf

            <button type="submit">
                {{ __('authentication.logout') }}
            </button>
        </form>
    </div>
</x-form-layout>
