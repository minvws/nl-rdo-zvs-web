@section('pageTitle', $publicHoliday->name . ' ' . strtolower(__('general.edit')))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('public_holiday.edit') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::ADMIN_PUBLIC_HOLIDAY_UPDATE, ['publicHoliday' => $publicHoliday]) }}">
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
                        maxlength="64"
                        id="name"
                        :hasError="$errors->has('name')"
                        type="text"
                        name="name"
                        aria-describedby="name-error"
                        value="{{ Form::old('name', $publicHoliday->name) }}" />
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
                        value="{{ Form::old('date', $publicHoliday->date->format('Y-m-d')) }}"
                        step="1" />
                </div>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('public_holiday.edit') }}
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
