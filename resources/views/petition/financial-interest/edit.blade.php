@section ("pageTitle", __("petition.edit_financial_interest"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.financial_interest") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#financial-interest-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.financial_interest") }} {{ __("general.edit") }}</span>
            </a>
        </header>
        @else
        <h1>{{ __("petition.financial_interest") }}</h1>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_UPDATE, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#financial-interest-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="financial-interest-block" />
            <h3 class="form-section__title form-section__title--border">
                {{ __("petition.edit_financial_interest") }}
            </h3>

            <div class="form-input-group">
                <x-input-label
                    for="amount-in-dispute"
                    :content="__('petition.amount_in_dispute')" />
                <x-input-error
                    id="amount-in-dispute-error"
                    :messages="$errors->get('amount_in_dispute')" />
                <input
                    class="form-control @error("amount_in_dispute") input-error @enderror"
                    type="number"
                    id="amount-in-dispute"
                    @if ($errors->has("amount_in_dispute")) aria-invalid="true" @endif
                    aria-describedby="amount-in-dispute-error"
                    name="amount_in_dispute"
                    min="0"
                    step="1"
                    value="{{ Form::old("amount_in_dispute", $petition->amount_in_dispute) }}" />
            </div>

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#financial-interest-block"
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
