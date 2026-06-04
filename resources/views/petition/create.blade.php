@use(App\Enums\Authorization\Permission)
@use(App\Enums\OptionalFormFieldSetting)

@section('pageTitle', __('petition.create_in') . ' ' . ActiveDepartment::getActiveDepartment()?->name)

<x-form-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __('petition.create') }}</h1>
        </div>
    </x-slot>

    <section>
        <div class="visually-grouped mt-5">
            <form
                method="post"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_STORE, ['department' => $department, 'petitionType' => $petitionType]) }}">
                @csrf
                <fieldset>
                    <legend class="form-legend">
                        {{ __('petition.details') }}
                    </legend>
                    @can(Permission::PETITION_NUMBER_OVERRULE->value)
                        <div class="form-input-group">
                            <x-input-label
                                for="number"
                                :content="__('Zaaknummer')" />
                            <x-input-error
                                id="number-error"
                                :messages="$errors->get('number')" />
                            <x-text-input
                                id="number"
                                :hasError="$errors->has('number')"
                                name="number"
                                type="text"
                                maxlength="64"
                                aria-describedby="number-error"
                                value="{{ Form::old('number') }}"
                                :placeholder="$nextPetitionNumber" />
                        </div>
                    @endcan

                    @if ($petitionTypeConfiguration['name'] !== OptionalFormFieldSetting::EXCLUDED)
                        <div class="form-input-group">
                            <x-input-label
                                for="name"
                                :required="$petitionTypeConfiguration['name'] === OptionalFormFieldSetting::REQUIRED"
                                help_text="{{ __('petition.create_help') }}"
                                :content="__('petition.name')" />
                            <x-input-error
                                id="name-error"
                                :messages="$errors->get('name')" />
                            <x-text-input
                                id="name"
                                :hasError="$errors->has('name')"
                                name="name"
                                type="text"
                                aria-describedby="name-error"
                                value="{{ Form::old('name') }}"
                                :placeholder="$petitionTypeConfiguration['name'] === OptionalFormFieldSetting::OPTIONAL ? __('petition.name_placeholder_optional') : null" />
                        </div>
                    @endif

                    @if ($petitionTypeConfiguration['description'] !== OptionalFormFieldSetting::EXCLUDED)
                        <div class="form-input-group">
                            <x-input-label
                                for="description"
                                :required="$petitionTypeConfiguration['description'] === OptionalFormFieldSetting::REQUIRED"
                                :content="__('petition.description')" />
                            <x-input-error
                                id="name-error"
                                :messages="$errors->get('description')" />
                            <textarea
                                class="form-control"
                                id="description"
                                name="description"></textarea>
                        </div>
                    @endif

                    @if ($petitionTypeConfiguration['petition_category_id'] !== OptionalFormFieldSetting::EXCLUDED)
                        <div class="form-input-group">
                            <x-input-label
                                for="petition-category-id"
                                :content="__('petition_category.model_singular')" />
                            <x-input-error
                                id="name-error"
                                :messages="$errors->get('petition_category_id')" />
                            <select
                                class="form-select"
                                id="petition-category-id"
                                name="petition_category_id">
                                @foreach ($petitionCategories as $petitionCategory)
                                    <option
                                        value="{{ $petitionCategory->id }}"
                                        @selected($petitionCategory->id->toString() === old('petition_category_id'))>
                                        {{ $petitionCategory->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-input-group">
                        <x-input-label
                            for="petition-team-id"
                            :content="__('team.model_singular')" />
                        <select
                            class="form-select"
                            id="petition-team-id"
                            name="team_id">
                            <option value="">{{ __('general.select') }}</option>
                            @foreach ($teams as $team)
                                <option
                                    value="{{ $team->id }}"
                                    @selected($team->id->toString() === old('team_id'))>
                                    {{ $team->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-input-group">
                        <x-input-label
                            for="date_of_entry"
                            required
                            :content="__(sprintf('petition.date_of_entry.%s', $petitionType->type->value))" />
                        <x-input-error
                            id="date-error"
                            :messages="$errors->get('date_of_entry')" />
                        <input
                            class="form-control @error('date_of_entry') input-error @enderror"
                            id="date_of_entry"
                            aria-describedby="date-error"
                            type="date"
                            name="date_of_entry"
                            value="{{ Form::old('date_of_entry', now()->toDateString()) }}"
                            step="1" />
                    </div>

                    @if ($petitionTypeConfiguration['date_appealed_decision'] !== OptionalFormFieldSetting::EXCLUDED)
                        <div class="form-input-group">
                            <x-input-label
                                for="date_appealed_decision"
                                :required="$petitionTypeConfiguration['date_appealed_decision'] === OptionalFormFieldSetting::REQUIRED"
                                :content="__(sprintf('petition.date_appealed_decision.%s', $petitionType->type->value))" />
                            <x-input-error
                                id="date_appealed_decision-error"
                                :messages="$errors->get('date_appealed_decision')" />
                            <input
                                class="form-control @error('date_appealed_decision') input-error @enderror"
                                id="date_appealed_decision"
                                aria-describedby="date_appealed_decision-error"
                                type="date"
                                name="date_appealed_decision"
                                value="{{ Form::old('date_appealed_decision', now()->toDateString()) }}"
                                step="1" />
                        </div>
                    @endif
                </fieldset>
                <div class="button-container">
                    <x-primary-button>
                        {{ __('general.create') }}
                    </x-primary-button>
                    <a
                        class="button"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]) }}">
                        {{ __('general.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </section>
</x-form-layout>
