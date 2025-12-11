@use(App\Enums\Authorization\Permission)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.custom_date') }}</h2>

        @can('update', [$petition])
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-dates-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.custom_date') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>

    <div class="petition-property__content">
        <dl class="description-list">
            <x-petition.custom-dates :petition="$petition" />
        </dl>
    </div>
</div>
