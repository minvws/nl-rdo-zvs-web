@use(App\Enums\RouteName)
@section('pageTitle', __('draft_term.create'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('draft_term.create') }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_DRAFT_TERM_STORE, ['department' => $petition->department->slug, 'petition' => $petition]) }}">
                @csrf

                @if ($errors->has('petition'))
                    <x-notification type="danger">
                        @foreach ($errors->get('petition') as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </x-notification>
                @endif

                <section>
                    <h2>{{ __('draft_term.details') }}</h2>

                    <div class="form-input-group">
                        <x-input-label
                            for="description"
                            :content="__('draft_term.description')" />
                        <x-input-error
                            id="description-error"
                            :messages="$errors->get('description')" />
                        <input
                            class="form-control @error('description') input-error @enderror"
                            id="description"
                            type="text"
                            name="description"
                            value="{{ Form::old('description') }}"
                            maxlength="255"
                            aria-describedby="description-error" />
                    </div>

                    <div class="form-input-group">
                        <x-input-label
                            for="event_date"
                            :content="__('draft_term.event_date')" />
                        <x-input-error
                            id="event_date-error"
                            :messages="$errors->get('event_date')" />
                        <input
                            class="form-control @error('event_date') input-error @enderror"
                            id="event_date"
                            type="date"
                            name="event_date"
                            value="{{ Form::old('event_date') }}"
                            aria-describedby="event_date-error" />
                    </div>

                    <div class="form-input-group">
                        <x-input-label
                            for="days_after_event"
                            :content="__('draft_term.days_after_event')" />
                        <x-input-error
                            id="days_after_event-error"
                            :messages="$errors->get('days_after_event')" />
                        <x-text-input
                            id="days_after_event"
                            name="days_after_event"
                            type="number"
                            min="0"
                            max="9999"
                            :hasError="$errors->has('days_after_event')"
                            :value="Form::old('days_after_event', '0')"
                            aria-describedby="days_after_event-error" />
                    </div>

                    <div class="form-input-group">
                        <x-input-label
                            for="date_withdrawal"
                            :content="__('draft_term.date_withdrawal')" />
                        <x-input-error
                            id="date_withdrawal-error"
                            :messages="$errors->get('date_withdrawal')" />
                        <input
                            class="form-control @error('date_withdrawal') input-error @enderror"
                            id="date_withdrawal"
                            type="date"
                            name="date_withdrawal"
                            value="{{ Form::old('date_withdrawal') }}"
                            aria-describedby="date_withdrawal-error" />
                    </div>

                    <div class="form-input-group">
                        <x-input-label
                            for="days_after_date_withdrawal"
                            :content="__('draft_term.days_after_date_withdrawal')" />
                        <x-input-error
                            id="days_after_date_withdrawal-error"
                            :messages="$errors->get('days_after_date_withdrawal')" />
                        <x-text-input
                            id="days_after_date_withdrawal"
                            name="days_after_date_withdrawal"
                            type="number"
                            min="0"
                            max="9999"
                            :hasError="$errors->has('days_after_date_withdrawal')"
                            :value="Form::old('days_after_date_withdrawal', '0')"
                            aria-describedby="days_after_date_withdrawal-error" />
                    </div>
                </section>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.save') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $petition->department->slug, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
