@section('pageTitle', __('public_holiday.create'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('public_holiday.create') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_STORE) }}">
                @csrf
                <div>
                    <x-input-label
                        for="name"
                        required
                        :content="__('general.name')" />
                    <x-input-error
                        id="name-error"
                        :messages="$errors->get('name')" />
                    <x-text-input
                        id="name"
                        maxlength="64"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        :value="old('name')" />
                </div>
                <div>
                    <x-input-label
                        for="date"
                        required
                        :content="__('general.date')" />
                    <x-input-error
                        id="date-error"
                        :messages="$errors->get('date')" />
                    <input
                        id="date"
                        :hasError="$errors->has('date')"
                        type="date"
                        name="date"
                        aria-describedby="date-error"
                        :value="{{ Form::old('date') }}"
                        step="1" />
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('public_holiday.create') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_INDEX) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
