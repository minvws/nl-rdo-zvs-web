@use(App\Enums\RouteName)
@use(App\Enums\Authorization\Permission)
@use(App\Enums\OptionalFormFieldSetting)

@use(App\Enums\SuspensionType)
@use(App\Enums\HearingForm)
@use(App\Enums\ResultType)
@use(Illuminate\Support\Facades\Lang)

@php
    $title = Str::ucfirst($selectedType->label($petition->petitionType->type)) . ' ' . Str::lower(__('petition_event.create'));
    $reasoningSelectEnumClass = $selectedType->reasoningSelectEnumClass();
    $selectedResultType = ResultType::tryFrom(old('result_type', request('result_type', $config['result_type']->value ?? null)));
    $reasoningValue = old('reasoning', request('reasoning', ''));
    $reasoningRequired = (bool) $selectedResultType?->requiresReasoning();
@endphp

@section('pageTitle', __('petition.create_in') . ' ' . ActiveDepartment::getActiveDepartment()?->name)

<x-form-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ Str::ucfirst($title) }}</h1>
        </div>
    </x-slot>

    @if ($errors->has('general'))
        @foreach ($errors->get('general') as $error)
            <div>
                {{ $error }}
            </div>
        @endforeach
    @endif

    <section>
        <div class="visually-grouped mt-5">
            <form
                method="post"
                action="{{ route(RouteName::PETITION_EVENTS_WIZARD_SUBMIT_FORM, ['department' => $department, 'petition' => $petition]) }}">
                @csrf
                <fieldset>
                    <legend class="form-legend">
                        {{ __('petition_event.details') }}
                    </legend>

                    <input
                        type="hidden"
                        name="type"
                        value="{{ $selectedType->value }}" />

                    <div class="form-input-group">
                        <x-input-label
                            for="date"
                            :required="true"
                            :content="__('petition_event.default.label.'. $selectedType->value )" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('date')" />
                        <x-text-input
                            id="date"
                            :hasError="$errors->has('date')"
                            name="date"
                            type="date"
                            aria-describedby="date-error"
                            value="{{ old('date', today()->toDateString()) }}"
                            required />
                    </div>

                    @if ($selectedType->hasSuspensionType())
                        <div class="form-input-group">
                            <x-input-label
                                for="suspension_type"
                                required
                                :content="__('petition_event.suspension_type')" />
                            <x-input-error
                                id="suspension_type-error"
                                :messages="$errors->get('suspension_type')" />
                            <select
                                id="suspension_type"
                                name="suspension_type"
                                class="form-control @error('suspension_type') input-error @enderror"
                                aria-describedby="suspension_type-error"
                                required>
                                <option value="">{{ __('general.select') }}</option>
                                @foreach (SuspensionType::getForPetitionType($petition->petitionType->type) as $type)
                                    <option
                                        value="{{ $type->value }}"
                                        @selected(old('suspension_type', $config['suspension_type']->value ?? null) === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($selectedType->hasResultType())
                        <div class="form-input-group">
                            <x-input-label
                                for="result_type"
                                required
                                :content="__('petition_event.result_type')" />
                            <x-input-error
                                id="result_type-error"
                                :messages="$errors->get('result_type')" />
                            <select
                                id="result_type"
                                name="result_type"
                                class="form-control @error('result_type') input-error @enderror"
                                aria-describedby="result_type-error"
                                hx-get="{{ route(RouteName::PETITION_EVENTS_WIZARD_CREATE, ['department' => $department, 'petition' => $petition, 'type' => $selectedType]) }}"
                                hx-trigger="change"
                                hx-target="#reasoning-wrapper-container"
                                hx-select="#reasoning-wrapper-container"
                                hx-swap="outerHTML"
                                hx-include="closest form"
                                required>
                                @foreach (ResultType::getGroupedForPetitionType($petition->petitionType->type) as $group => $types)
                                    @if (count($types) > 0)
                                        <optgroup label="{{ __('petition_event.result_type_optgroup_' . $group) }}">
                                            @foreach ($types as $type)
                                                <option
                                                    value="{{ $type->value }}"
                                                    @selected(old('result_type', $config['result_type']->value ?? null) === $type->value)>
                                                    {{ $resultTypeLabels[$type->value] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div id="reasoning-wrapper-container">
                        @if ($selectedType->hasReasoningTextarea($selectedResultType))
                            <div
                                id="reasoning-wrapper"
                                class="form-input-group">
                                <x-input-label
                                    for="reasoning"
                                    :required="$reasoningRequired"
                                    :content="__('petition_event.result_type_other_reasoning')" />
                                <x-input-error
                                    id="reasoning-error"
                                    :messages="$errors->get('reasoning')" />
                                <input
                                    id="reasoning"
                                    name="reasoning"
                                    type="text"
                                    class="form-control @error('reasoning') input-error @enderror"
                                    aria-describedby="reasoning-error"
                                    maxlength="1000"
                                    value="{{ $reasoningValue }}"
                                    @required($reasoningRequired) />
                            </div>
                        @endif
                    </div>

                    @if ($selectedType->hasHearingForm())
                        <div class="form-input-group">
                            <x-input-label
                                for="hearing_form"
                                required
                                :content="__('petition_event.hearing_form')" />
                            <x-input-error
                                id="hearing_form-error"
                                :messages="$errors->get('hearing_form')" />
                            <select
                                id="hearing_form"
                                name="hearing_form"
                                class="form-control @error('hearing_form') input-error @enderror"
                                aria-describedby="hearing_form-error"
                                required>
                                @foreach (HearingForm::cases() as $form)
                                    <option
                                        value="{{ $form->value }}"
                                        @selected(old('hearing_form', $config['hearing_form']->value ?? null) === $form->value)>
                                        {{ $form->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($reasoningSelectEnumClass !== null)
                        <div class="form-input-group">
                            <x-input-label
                                for="reasoning"
                                required
                                :content="__('petition_event.reasoning')" />
                            <x-input-error
                                id="reasoning-error"
                                :messages="$errors->get('reasoning')" />
                            <select
                                id="reasoning"
                                name="reasoning"
                                class="form-control @error('reasoning') input-error @enderror"
                                aria-describedby="reasoning-error"
                                required>
                                <option value="">{{ __('general.select') }}</option>
                                @foreach ($reasoningSelectEnumClass::cases() as $reason)
                                    <option
                                        value="{{ $reason->value }}"
                                        @selected(old('reasoning') === $reason->value)>
                                        {{ $reason->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($selectedType->hasEndDate())
                        <div class="form-input-group">
                            <x-input-label
                                for="term_deadline"
                                required
                                :content="Lang::has('petition_event.deadline.' . $selectedType->value) ? __('petition_event.deadline.' . $selectedType->value) : __('petition_event.default.label.term_deadline')" />
                            <x-input-error
                                id="term_deadline-error"
                                :messages="$errors->get('term_deadline')" />
                            <x-text-input
                                id="term_deadline"
                                :hasError="$errors->has('term_deadline')"
                                name="term_deadline"
                                type="date"
                                aria-describedby="term_deadline-error"
                                required
                                value="{{ old('term_deadline', $config['term_deadline'] ?? null) }}" />
                        </div>
                        <input
                            type="hidden"
                            id="duration"
                            name="duration"
                            value="{{ Form::old('duration', $config['duration'] ?? null) }}" />
                    @elseif ($selectedType->hasDuration())
                        <div class="form-input-group">
                            @if (Lang::has('petition_event.fieldset_title.duration.' . $selectedType->value))
                                <legend>
                                    {{ __('petition_event.fieldset_title.duration.' . $selectedType->value . '.title') }}
                                </legend>
                                <p class="form-legend__help">
                                    {{ __('petition_event.fieldset_title.duration.' . $selectedType->value . '.subtitle') }}
                                </p>
                            @endif

                            <x-input-label
                                for="duration"
                                required
                                :content="Lang::has('petition_event.duration.' . $selectedType->value) ? __('petition_event.duration.' . $selectedType->value) : __('petition_event.default.label.term_duration')" />
                            <x-input-error
                                id="duration-error"
                                :messages="$errors->get('duration')" />
                            <x-text-input
                                id="duration"
                                :hasError="$errors->has('duration')"
                                type="number"
                                name="duration"
                                aria-describedby="duration-error"
                                required
                                value="{{ Form::old('duration', $config['duration'] ?? null) }}" />
                        </div>
                    @endif

                    @if ($selectedType->hasPenalties($petition->petitionType->type))
                        <fieldset class="form-group">
                            <legend>{{ __('petition_event.penalties.title') }}</legend>
                            <p class="form-legend__help">{{ __('petition_event.penalties.subtitle') }}</p>

                            <table>
                                <thead>
                                    <tr>
                                        <th>Duration (dagen)</th>
                                        <th>Bedrag (€)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($i = 0; $i < 3; $i++)
                                        <tr>
                                            <td>
                                                <input
                                                    type="number"
                                                    id="penalties_{{ $i }}_duration"
                                                    name="penalties[{{ $i }}][duration]"
                                                    value="{{ old('penalties.' . $i . '.duration', $config['penalties'][$i]['duration'] ?? null) }}"
                                                    min="0"
                                                    placeholder="Dagen" />
                                                <x-input-error
                                                    :messages="$errors->get('penalties.' . $i . '.duration')"
                                                    :id="'penalties_' . $i . '_duration-error'" />
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    id="penalties_{{ $i }}_amount"
                                                    name="penalties[{{ $i }}][amount]"
                                                    value="{{ old('penalties.' . $i . '.amount', $config['penalties'][$i]['amount'] ?? null) }}"
                                                    min="0"
                                                    step="1"
                                                    placeholder="Euro's" />
                                                <x-input-error
                                                    :messages="$errors->get('penalties.' . $i . '.amount')"
                                                    :id="'penalties_' . $i . '_amount-error'" />
                                            </td>
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </fieldset>
                    @endif
                </fieldset>

                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.create') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::PETITION_EVENTS_WIZARD_STEP, ['department' => $department, 'petition' => $petition]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
