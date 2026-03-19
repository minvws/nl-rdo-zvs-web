@use(App\Enums\RouteName)

@section('pageTitle', __('general.confirm_action'))

<x-app-layout>
    <x-slot name="header">
        <h1>
            {{ __('general.confirm_action') }}
        </h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <p>{{ $message }}</p>

            <form
                class="mt-3"
                method="post"
                action="{{ $confirmUrl }}">
                @csrf
                <div class="button-container">
                    <button
                        type="submit"
                        name="action"
                        value="confirm"
                        class="primary-button">
                        {{ __('general.yes') }}
                    </button>
                    <a
                        href="{{ $cancelUrl }}"
                        class="button">
                        {{ __('general.no') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-app-layout>
