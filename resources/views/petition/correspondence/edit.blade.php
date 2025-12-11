@section("pageTitle", __("petition_correspondence.edit"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition_correspondence.correspondence") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#correspondence-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">
                    {{ __("petition_correspondence.correspondence") }} {{ __("general.edit") }}
                </span>
            </a>
        </header>
    @else
        <h1>{{ __("petition_correspondence.correspondence") }}</h1>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#correspondence-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="correspondence-block" />
            <h3 class="form-section__title form-section__title--border">
                {{ __("petition_correspondence.edit_message") }}
            </h3>

            <div class="form-input-group">
                <x-input-label
                    class="message-label"
                    for="message"
                    :content="__('petition_correspondence.message')" />
                <x-input-error
                    id="message-error"
                    :messages="$errors->get('message')" />
                <x-text-input
                    id="message"
                    :hasError="$errors->has('message')"
                    aria-describedby="message-error"
                    type="text"
                    name="message"
                    value="{{ Form::old('message', $petition->message) }}" />
            </div>
            <div class="form-input-group">
                <x-input-label
                    class="date-of-message-label"
                    for="date-of-message"
                    :content="__('petition_correspondence.date_of_message')" />
                <x-input-error
                    id="date-required-error"
                    :messages="$errors->get('date_of_message')" />
                <input
                    class="form-control @error("date_of_message") input-error @enderror"
                    type="date"
                    id="date-of-message"
                    :hasError="$errors->has('date_of_message')"
                    @error("date_of_message")
                        aria-invalid="true"
                    @enderror
                    aria-describedby="date-required-error"
                    name="date_of_message"
                    value="{{ Form::old("date_of_message", $petition->date_of_message) }}" />
            </div>

            <div class="form-input-group">
                <x-input-label
                    class="decision-reference-label"
                    for="decision-reference"
                    :content="__('petition_correspondence.decision_reference')" />
                <x-input-error
                    id="decision-reference-error"
                    :messages="$errors->get('decision_reference')" />
                <x-text-input
                    id="decision-reference"
                    :hasError="$errors->has('decision_reference')"
                    aria-describedby="decision-reference-error"
                    type="text"
                    name="decision_reference"
                    value="{{ Form::old('decision_reference', $petition->decision_reference) }}" />
            </div>

            <div class="form-input-group">
                <x-input-label
                    class="decision-date-label"
                    for="decision-date"
                    :content="__('petition_correspondence.decision_date')" />
                <x-input-error
                    id="decision-date-error"
                    :messages="$errors->get('decision_date')" />
                <input
                    class="form-control @error("decision_date") input-error @enderror"
                    type="date"
                    id="decision-date"
                    :hasError="$errors->has('decision_date')"
                    @error("decision_date")
                        aria-invalid="true"
                    @enderror
                    aria-describedby="decision-date-error"
                    name="decision_date"
                    value="{{ Form::old("decision_date", $petition->decision_date) }}" />
            </div>

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#correspondence-block"
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
