@section('pageTitle', __('petition.attach'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('petition.attach') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH, ['department' => $decision->department->slug, 'decision' => $decision]) }}">
                @csrf

                <div>
                    <x-input-label
                        for="number"
                        required
                        :content="__('petition.number')" />
                    <x-input-error
                        id="number-error"
                        :messages="$errors->get('number')" />
                    <x-text-input
                        id="number"
                        :hasError="$errors->has('number')"
                        type="text"
                        name="number"
                        aria-describedby="number-error"
                        value="{{ Form::old('number') }}" />
                </div>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('petition.attach') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department->slug, 'decision' => $decision]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
