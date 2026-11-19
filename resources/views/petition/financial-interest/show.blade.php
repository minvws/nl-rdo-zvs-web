@use (App\Enums\Ability)
@use (App\Enums\RouteName)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.financial_interest') }}</h2>
        @can (Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_FINANCIAL_INTEREST_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#financial-interest-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.financial_interest') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        <dl class="description-list">
            <div class="description-list__item">
                <dt>{{ __('petition.amount_in_dispute') }}</dt>
                <dd>
                    {{ $petition->amount_in_dispute !== null ? Number::currency($petition->amount_in_dispute, 'EUR', 'nl', 0) : '-' }}
                </dd>
            </div>
        </dl>
    </div>
</div>
