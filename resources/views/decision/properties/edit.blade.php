@section("pageTitle", __("petition.edit_properties"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ $decision->type->label() }}</h2>
            <a
                href="{{ route(RouteName::DEPARTMENTS_DECISIONS_PROPERTIES, ["department" => $decision->department->slug, "decision" => $decision->id]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#properties-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("general.edit") }}</span>
            </a>
        </header>
    @else
        <h1>{{ __("petition.edit_properties") }}</h1>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_DECISIONS_UPDATE, ["department" => $decision->department->slug, "decision" => $decision->id]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#properties-block">
            @csrf
            <input
                type="hidden"
                name="decision_id"
                value="{{ $decision->id }}" />
            <input
                type="hidden"
                name="hx-target"
                value="properties-block" />
            <h3 class="form-section__title form-section__title--border">{{ __("decision.edit_properties") }}</h3>
            <div>
                <x-input-label
                    for="decision-name"
                    required
                    :content="__('decision.name')" />
                <x-input-error
                    id="name-error"
                    :messages="$errors->get('name')" />
                <x-text-input
                    id="decision-name"
                    :hasError="$errors->has('name')"
                    name="name"
                    type="text"
                    aria-describedby="name-error"
                    value="{{ Form::old('name', $decision->name) }}" />
            </div>
            <div>
                <x-input-label
                    for="decision-reference"
                    :content="__('decision.reference')" />
                <x-input-error
                    id="reference-error"
                    :messages="$errors->get('reference')" />
                <x-text-input
                    id="decision-reference"
                    :hasError="$errors->has('reference')"
                    name="reference"
                    type="text"
                    aria-describedby="reference-error"
                    value="{{ Form::old('reference', $decision->reference) }}" />
            </div>
            <div>
                <x-input-label
                    for="decision-date"
                    :content="__('decision.date')" />
                <x-input-error
                    id="date_of_entry-error"
                    :messages="$errors->get('date')" />
                <input
                    id="decision-date"
                    :hasError="$errors->has('date')"
                    class="form-control"
                    type="date"
                    name="date"
                    step="1"
                    value="{{ Form::old("date", $decision->date) }}" />
            </div>
            <div>
                <x-input-label
                    for="decision-reviewbatch"
                    :content="__('decision.reviewbatch')" />
                <x-input-error
                    id="reviewbatch-error"
                    :messages="$errors->get('reviewbatch')" />
                <x-text-input
                    id="decision-reviewbatch"
                    :hasError="$errors->has('reviewbatch')"
                    name="reviewbatch"
                    type="text"
                    maxlength="128"
                    aria-describedby="reviewbatch-error"
                    value="{{ Form::old('reviewbatch', $decision->reviewbatch) }}" />
            </div>

            <div>
                <x-input-label
                    for="decision-team-id"
                    :content="__('team.model_singular')" />
                <select
                    class="form-select"
                    id="decision-team-id"
                    name="team_id">
                    <option value="">{{ __("general.select") }}</option>
                    @foreach ($teams as $team)
                        <option
                            value="{{ $team->id }}"
                            @selected($team->id->toString() === Form::old("team_id", $decision->team_id?->toString()))>
                            {{ $team->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            @if ($errors->any())
                <x-notification type="danger">
                    <p>@lang("validation.global_message")</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>
                    {{ __("general.save") }}
                </x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_DECISIONS_PROPERTIES, ["department" => $decision->department->slug, "decision" => $decision]) }}"
                        hx-target="#properties-block"
                        hx-swap="innerHTML"
                    @else
                        href="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ["department" => $decision->department->slug, "decision" => $decision]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
