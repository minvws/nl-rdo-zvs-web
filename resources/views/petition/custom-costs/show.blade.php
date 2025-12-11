@use(App\Enums\RouteName)

@section('pageTitle', __('petition.custom_cost'))

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.custom_cost') }}</h2>
        @can('update', [$petition])
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_COSTS_EDIT, ['department' => $petition->department, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-costs-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.custom_cost') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->customCosts->isEmpty())
            <p>{{ '-' }}</p>
        @else
            <dl class="property-list">
                @foreach ($petition->customCosts as $cost)
                    <div class="property-list__item">
                        <dt class="property-list__term">
                            {{ __('custom_cost_type.' . $cost->custom_cost_type->value) }}
                        </dt>
                        <dd class="property-list__description">
                            {{ Number::currency($cost->amountInEuros, in: 'EUR', locale: 'nl_NL', precision: 2) }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>
