@section('pageTitle', __('term.edit'))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('term.edit', ['term' => __(sprintf('term.term_type.%s', $term->type->value))]) }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{
                    route(RouteName::DEPARTMENTS_PETITIONS_TERMS_EDIT, [
                        'department' => $department,
                        'petition' => $petition,
                        'petitionTerm' => $term->id,
                    ])
                }}">
                @csrf

                @if ($departmentTermTypeSettings->firstWhere('field', 'start_date')?->active)
                    <div class="form-input-group">
                        <x-input-label
                            for="start-date"
                            :content="__('term.start_date')" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('start_date')" />
                        <input
                            class="form-control @error('start_date') input-error @enderror"
                            id="start-date"
                            aria-describedby="date-error"
                            type="date"
                            name="start_date"
                            value="{{ Form::old('start_date', $term->start_date) }}" />
                    </div>
                @endif

                @if ($departmentTermTypeSettings->firstWhere('field', 'duration_in_days')?->active)
                    <div>
                        <x-input-label
                            for="duration"
                            :content="__('term.duration_in_days')" />
                        <x-input-error
                            id="duration-error"
                            :messages="$errors->get('duration_in_days')" />
                        <x-text-input
                            id="duration"
                            :hasError="$errors->has('duration_in_days')"
                            type="number"
                            name="duration_in_days"
                            aria-describedby="duration-error"
                            value="{{ Form::old('duration_in_days', $term->duration_in_days) }}" />
                    </div>
                @endif

                @if ($departmentTermTypeSettings->firstWhere('field', 'penalty_amount_in_euros')?->active)
                    <div>
                        <x-input-label
                            for="amount"
                            :content="__('term.penalty_amount_in_euros')" />
                        <x-input-error
                            id="amount-error"
                            :messages="$errors->get('penalty_amount_in_euros')" />
                        <x-text-input
                            id="amount"
                            :hasError="$errors->has('penalty_amount_in_euros')"
                            type="number"
                            name="penalty_amount_in_euros"
                            aria-describedby="amount-error"
                            value="{{ Form::old('penalty_amount_in_euros', $term->penalty_amount_in_euros) }}" />
                    </div>
                @endif

                @if ($departmentTermTypeSettings->firstWhere('field', 'end_date')?->active)
                    <div class="form-input-group">
                        <x-input-label
                            for="end-date"
                            :content="__('term.end_date')" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('end_date')" />
                        <input
                            class="form-control @error('end_date') input-error @enderror"
                            id="end-date"
                            aria-describedby="date-error"
                            type="date"
                            name="end_date"
                            value="{{ Form::old('end_date', $term->end_date) }}" />
                    </div>
                @endif

                <div class="button-container">
                    <x-primary-button>
                        {{ __('term.edit', ['term' => __(sprintf('term.term_type.%s', $term->type->value))]) }}
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
