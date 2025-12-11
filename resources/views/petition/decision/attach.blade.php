@section('pageTitle', __('decision.attach'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('decision.attach') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH, ['department' => $department, 'petition' => $petition]) }}">
                @csrf

                <div>
                    <x-input-label
                        for="reference"
                        required
                        :content="__('decision.reference')" />
                    <x-input-error
                        id="reference-error"
                        :messages="$errors->get('reference')" />
                    <x-text-input
                        id="reference"
                        :hasError="$errors->has('reference')"
                        type="text"
                        name="reference"
                        aria-describedby="reference-error"
                        value="{{ Form::old('reference') }}" />
                </div>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('decision.attach') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
