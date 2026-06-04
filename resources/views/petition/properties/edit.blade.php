@use(App\Enums\OptionalFormFieldSetting)

@section("pageTitle", __("petition.edit_properties"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.model_singular") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#properties-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.model_singular") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @else
        <h1>{{ __("petition.edit_properties") }}</h1>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#properties-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="properties-block" />

            <h3 class="form-section__title form-section__title--border">{{ __("petition.edit_properties") }}</h3>

            @if ($petitionTypeConfiguration["name"] !== OptionalFormFieldSetting::EXCLUDED)
                <div>
                    <x-input-label
                        for="petition-name"
                        :required="$petitionTypeConfiguration['name'] === OptionalFormFieldSetting::REQUIRED"
                        :content="__('petition.name')" />
                    <x-input-error
                        id="name-error"
                        :messages="$errors->get('name')" />
                    <x-text-input
                        id="petition-name"
                        :hasError="$errors->has('name')"
                        name="name"
                        type="text"
                        aria-describedby="name-error"
                        value="{{ Form::old('name', $petition->name) }}"
                        :placeholder="$petitionTypeConfiguration['name'] === OptionalFormFieldSetting::OPTIONAL ? __('petition.name_placeholder_optional') : null" />
                </div>
            @endif

            @if ($petitionTypeConfiguration["description"] !== OptionalFormFieldSetting::EXCLUDED)
                <div>
                    <x-input-label
                        for="petition-description"
                        :required="$petitionTypeConfiguration['description'] === OptionalFormFieldSetting::REQUIRED"
                        :content="__('petition.description')" />
                    <textarea
                        class="form-control"
                        id="petition-description"
                        name="description">
{{ Form::old("description", $petition->description) }}</textarea
                    >
                </div>
            @endif

            @if ($petitionTypeConfiguration["petition_category_id"] !== OptionalFormFieldSetting::EXCLUDED)
                <div class="form-input-group">
                    <x-input-label
                        for="petition-category-id"
                        :content="__('petition_category.model_singular')" />
                    <select
                        class="form-select"
                        id="petition-category-id"
                        name="petition_category_id">
                        <option value="">{{ __("general.select") }}</option>
                        @foreach ($petitionCategories as $petitionCategory)
                            <option
                                value="{{ $petitionCategory->id }}"
                                @selected($petitionCategory->id->toString() === Form::old("petition_category_id", $petition->petition_category_id?->toString()))>
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
                    <option value="">{{ __("general.select") }}</option>
                    @foreach ($teams as $team)
                        <option
                            value="{{ $team->id }}"
                            @selected($team->id->toString() === Form::old("team_id", $petition->team_id?->toString()))>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <x-input-label
                    for="petition-type-id"
                    :content="__('petition_type.case_type')" />
                <x-text-input
                    disabled
                    type="text"
                    name="petition-type-id"
                    aria-describedby="petition-type-id-error"
                    value="{{ $petition->petitionType->name }}" />
            </div>

            <div>
                <x-input-label
                    for="petition-entry-date"
                    required
                    :content="__(sprintf('petition.date_of_entry.%s', $petition->petitionType->type->value))" />
                <x-input-error
                    id="date_of_entry-error"
                    :messages="$errors->get('date_of_entry')" />
                <input
                    id="petition-entry-date"
                    :hasError="$errors->has('date_of_entry')"
                    class="form-control"
                    type="date"
                    name="date_of_entry"
                    step="1"
                    value="{{ Form::old("date_of_entry", $petition->date_of_entry) }}" />
            </div>

            @if ($petitionTypeConfiguration["date_appealed_decision"] !== OptionalFormFieldSetting::EXCLUDED)
                <div>
                    <x-input-label
                        for="petition-date-appealed-decision"
                        required
                        :content="__(sprintf('petition.date_appealed_decision.%s', $petition->petitionType->type->value))" />
                    <x-input-error
                        id="date_appealed_decision-error"
                        :messages="$errors->get('date_appealed_decision')" />
                    <input
                        id="petition-date-appealed-decision"
                        :hasError="$errors->has('date_appealed_decision')"
                        class="form-control"
                        type="date"
                        name="date_appealed_decision"
                        step="1"
                        value="{{ Form::old("date_appealed_decision", $petition->date_appealed_decision) }}" />
                </div>
            @endif

            @if ($errors->any())
                <x-notification type="danger">
                    <p>{{ __("validation.global_message") }}</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>
                    {{ __("general.save") }}
                </x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#properties-block"
                        hx-swap="innerHTML"
                    @else
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
