@use(App\Enums\Ability)
@use(App\Enums\CustomPetitionPropertyType)
@use(App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ $customPetitionProperties->shift()?->name }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_PETITION_PROPERTY_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-petition-properties-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">
                    {{ $customPetitionProperties->shift()?->name }} {{ __('general.edit') }}
                </span>
            </a>
        @endcan
    </header>

    <div class="petition-property__content custom-petition-properties__content">
        @foreach ($customPetitionProperties as $customPetitionProperty)
            @switch($customPetitionProperty->type)
                @case(CustomPetitionPropertyType::SUBTITLE)
                    <h3 class="custom-petition-properties__subtitle">{{ $customPetitionProperty->name }}</h3>

                    @break
                @case(CustomPetitionPropertyType::OPTION)
                    <p class="custom-petition-properties__option">{{ $customPetitionProperty->name }}</p>

                    @break
                @case(CustomPetitionPropertyType::NO_SELECTED_OPTIONS)
                    <p class="custom-petition-properties__option">
                        {{ __('petition.custom_petition_properties_no_selected_options') }}
                    </p>
            @endswitch
        @endforeach
    </div>
</div>
