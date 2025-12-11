@use(App\Enums\CustomPetitionPropertyType)
@use(App\Enums\RouteName)

@section("pageTitle", __("exports.export_overview"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ $custom_petition_properties->shift()?->name }}</h2>
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-petition-properties-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">
                    {{ $custom_petition_properties->shift()?->name }} {{ __("general.edit") }}
                </span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_UPDATE, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#custom-petition-properties-block">
            @csrf

            @foreach ($custom_petition_properties as $custom_petition_property)
                @if ($custom_petition_property->type !== CustomPetitionPropertyType::OPTION)
                    <h3
                        @class(["form-section__title form-section__title--border" => $custom_petition_property->type === CustomPetitionPropertyType::TITLE])>
                        {{ $custom_petition_property->name }}
                    </h3>
                @else
                    <div
                        class="checkbox @if ($errors->has($custom_petition_property->id->toString()) && ! $errors->has("custom_petition_properties.*"))
                            checkbox--error
                        @endif">
                        <input
                            id="{{ $custom_petition_property->id }}"
                            data-group="{{ $custom_petition_property->grouping }}"
                            type="checkbox"
                            name="custom_petition_properties[]"
                            value="{{ $custom_petition_property->id }}"
                            @checked(in_array(
                                $custom_petition_property->id,
                                old("custom_petition_properties", $petition_custom_petition_property_ids),
                            )) />
                        <x-input-label
                            for="{{$custom_petition_property->id}}"
                            :content="$custom_petition_property->name" />
                        <x-input-error
                            id="property-error"
                            :messages="$errors->get($custom_petition_property->id->toString())"
                            class="mt-2" />
                    </div>
                @endif
            @endforeach

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#custom-petition-properties-block"
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
