@section("pageTitle", __("petition.edit_custom_cost"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.custom_cost") }}</h2>
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-costs-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.custom_cost") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @else
        <h1>{{ __("petition.edit_custom_cost") }}</h1>
    @endifHtmx

    <div class="petition-property__content">
        <form
            id="custom-costs-form"
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_UPDATE, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#custom-costs-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="custom-costs-block" />

            @foreach ($availableCostTypes as $index => $costType)
                <div class="form-input-group">
                    <x-input-label
                        for="custom-costs-{{ $index }}"
                        :content="__('custom_cost_type.' . $costType)" />

                    <div class="form-input-group__wrapper">
                        <x-text-input
                            class="form-control"
                            id="custom-costs-{{ $index }}"
                            maxlength="255"
                            type="hidden"
                            name="custom_costs[{{ $index }}][custom_cost_type]"
                            value="{{ $costType }}" />
                    </div>
                    <div class="form-input-group__wrapper">
                        <input
                            class="form-control @error("custom_costs.{$index}.custom_cost_amount_in_euros") input-error @enderror"
                            type="number"
                            min="0"
                            max="1000000000"
                            step="0.01"
                            id="custom-costs-amount-{{ $index }}"
                            aria-describedby="amount-required"
                            name="custom_costs[{{ $index }}][custom_cost_amount_in_euros]"
                            value="{{
                                number_format(
                                    old(
                                        "custom_costs.{$index}.custom_cost_amount_in_euros",
                                        $petition->customCosts->firstWhere("custom_cost_type", $costType)?->amountInEuros ?? 0,
                                    ),
                                    2,
                                    ".",
                                    "",
                                )
                            }}" />
                    </div>
                    <div>
                        <x-input-error
                            id="custom-costs-amount-{{ $index }}"
                            :messages="$errors->get('custom_costs.'.$index.'.custom_cost_amount_in_euros')" />
                    </div>
                </div>
            @endforeach

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#custom-costs-block"
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
