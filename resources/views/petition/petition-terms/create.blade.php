@section('pageTitle', __('term.create', ['term' => __(sprintf('term.term_type.%s', $termType->value))]))

<x-form-layout>
    <x-slot name="header">
        <h1>{{ __('term.create', ['term' => __(sprintf('term.term_type.%s', $termType->value))]) }}</h1>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped">
            <form
                method="post"
                action="{{
                    route(RouteName::DEPARTMENTS_PETITIONS_TERMS_STORE, [
                        'department' => $petition->department->slug,
                        'petition' => $petition,
                        'termType' => $termType,
                    ])
                }}">
                @csrf

                <section>
                    <h2>{{ __(sprintf('term.term_type.%s', $termType->value)) }}</h2>

                    @if ($departmentTermTypeSettings->firstWhere('field', 'start_date')?->active)
                        <div class="form-input-group">
                            <x-input-label
                                for="date-of-entry"
                                required
                                :content="$departmentTermTypeSettings->firstWhere('field', 'start_date')->title ?? __(sprintf('term.term_type_start_date.%s', $termType->value))" />
                            <x-input-error
                                id="date-error"
                                :messages="$errors->get('start_date')" />
                            <input
                                class="form-control @error('start_date') input-error @enderror"
                                id="date-of-entry"
                                aria-describedby="start_date-error"
                                type="date"
                                name="start_date"
                                value="{{ Form::old('start_date', now()->addDays((int) $departmentTermTypeSettings->firstWhere('field', 'start_date')->default_value)->toDateString(),) }}" />
                        </div>
                    @endif

                    @if ($departmentTermTypeSettings->firstWhere('field', 'duration_in_days')?->active)
                        <div class="form-input-group">
                            <x-input-label
                                for="duration"
                                required
                                :content="$departmentTermTypeSettings->firstWhere('field', 'duration_in_days')->title ?? __('term.duration_in_days')" />
                            <x-input-error
                                id="duration-error"
                                :messages="$errors->get('duration_in_days')" />
                            <x-text-input
                                id="duration"
                                :hasError="$errors->has('duration_in_days')"
                                type="number"
                                name="duration_in_days"
                                aria-describedby="duration_in_days-error"
                                value="{{ Form::old('duration_in_days', $departmentTermTypeSettings->firstWhere('field', 'duration_in_days')->default_value) }}" />
                        </div>
                    @endif

                    @if ($departmentTermTypeSettings->firstWhere('field', 'penalty_amount_in_euros')?->active)
                        <div>
                            <x-input-label
                                for="amount"
                                :content="$departmentTermTypeSettings->firstWhere('field', 'penalty_amount_in_euros')->title ?? __('term.penalty_amount_in_euros')" />
                            <x-input-error
                                id="amount-error"
                                :messages="$errors->get('penalty_amount_in_euros')" />
                            <x-text-input
                                id="amount"
                                :hasError="$errors->has('penalty_amount_in_euros')"
                                type="number"
                                name="penalty_amount_in_euros"
                                aria-describedby="amount-error"
                                value="{{ Form::old('penalty_amount_in_euros', $departmentTermTypeSettings->firstWhere('field', 'penalty_amount_in_euros')->default_value) }}" />
                        </div>
                    @endif
                </section>

                @if ($departmentTermTypeSettings->firstWhere('field', 'end_date')?->active)
                    <div class="form-input-group">
                        <x-input-label
                            for="end_date"
                            required
                            :content="$departmentTermTypeSettings->firstWhere('field', 'end_date')->title ?? __('term.end_date')" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('end_date')" />
                        <input
                            class="form-control @error('end_date') input-error @enderror"
                            id="end_date"
                            aria-describedby="end_date-error"
                            type="date"
                            name="end_date"
                            value="{{ Form::old('end_date', $petition->date_of_entry->addDays((int) $departmentTermTypeSettings->firstWhere('field', 'end_date')->default_value)->toDateString()) }}" />
                    </div>
                @endif

                @if ($departmentTermTypeSettings->firstWhere('field', 'date_appealed_decision')?->active)
                    <div class="form-input-group">
                        <x-input-label
                            for="date_appealed_decision"
                            required
                            :content="$departmentTermTypeSettings->firstWhere('field', 'date_appealed_decision')->title ?? __('term.date_appealed_decision')" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('date_appealed_decision')" />
                        <input
                            class="form-control @error('date_appealed_decision') input-error @enderror"
                            id="date_appealed_decision"
                            aria-describedby="date_appealed_decision-error"
                            type="date"
                            name="date_appealed_decision"
                            value="{{ Form::old('date_appealed_decision', now()->addDays((int) $departmentTermTypeSettings->firstWhere('field', 'date_appealed_decision')->default_value)->toDateString(),) }}" />
                    </div>
                @endif

                @if ($departmentTermTypeSettings->firstWhere('field', 'penalty_terms')?->active)
                    <h2>{{ __('term.penalties') }}</h2>
                    @foreach (json_decode($departmentTermTypeSettings->firstWhere('field', 'penalty_terms')->default_value) as $penaltyTerm)
                        <section>
                            <h3>{{ __('term.penalty') }} #{{ $loop->iteration }}</h3>
                            <div>
                                <x-input-label
                                    for="penalty_terms[{{ $loop->index }}][duration_in_days]"
                                    :content="$penaltyTerm->title ?? __('term.duration_in_days')" />
                                <x-input-error
                                    id="penalty_terms[{{ $loop->index }}][duration_in_days]-error"
                                    :messages="$errors->get(sprintf('penalty_terms.%s.duration_in_days', $loop->index))" />
                                <x-text-input
                                    type="number"
                                    name="penalty_terms[{{ $loop->index }}][duration_in_days]"
                                    value="{{ Form::old(sprintf('penalty_terms.%s.duration_in_days', $loop->index), $penaltyTerm->duration_in_days) }}" />
                            </div>
                            <div>
                                <x-input-label
                                    for="penalty_terms[{{ $loop->index }}][penalty_amount_in_euros]"
                                    :content="__('term.penalty_amount_in_euros')" />
                                <x-input-error
                                    id="penalty_terms[{{ $loop->index }}][penalty_amount_in_euros]-error"
                                    :messages="$errors->get(sprintf('penalty_terms.%s.penalty_amount_in_euros', $loop->index))" />
                                <x-text-input
                                    type="number"
                                    name="penalty_terms[{{ $loop->index }}][penalty_amount_in_euros]"
                                    value="{{ Form::old(sprintf('penalty_terms.%s.penalty_amount_in_euros', $loop->index), $penaltyTerm->penalty_amount_in_euros) }}" />
                            </div>
                        </section>
                    @endforeach
                @endif

                <div class="button-container">
                    <x-primary-button>
                        {{ __('term.create', ['term' => __(sprintf('term.term_type.%s', $termType->value))]) }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{
                            route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                                'department' => $petition->department->slug,
                                'petition' => $petition,
                            ])
                        }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
