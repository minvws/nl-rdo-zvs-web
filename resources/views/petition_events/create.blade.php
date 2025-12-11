@use(App\Enums\RouteName)
@use(App\Enums\Authorization\Permission)
@use(App\Enums\OptionalFormFieldSetting)

@use(App\Enums\SuspensionType)
@use(App\Enums\ResultType)

@php
    $title = Str::lower($selectedType->label($petition->petitionType->type)) . ' ' . Str::lower(__('petition_event.create'));
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
                            :content="__('petition_event.date.'. ($selectedType->value ?? 'default') )" />
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
                                required>
                                <option value="">{{ __('general.select') }}</option>
                                @foreach (ResultType::getForPetitionType($petition->petitionType->type) as $type)
                                    <option
                                        value="{{ $type->value }}"
                                        @selected(old('result_type', $config['result_type']->value ?? null) === $type->value)>
                                        {{ $type->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    @if ($selectedType->hasDuration())
                        <div class="form-input-group">
                            <x-input-label
                                for="duration"
                                required
                                :content="__('petition_event.duration.'. ($selectedType->value ?? 'default') )" />
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

                    @if ($selectedType->hasPenalties())
                        <fieldset class="form-group">
                            <legend>Boetes (max. 3) - vul alleen in wat nodig is</legend>

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
