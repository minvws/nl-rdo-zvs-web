@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition_correspondence.correspondence') }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#correspondence-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">
                    {{ __('petition_correspondence.correspondence') }} {{ __('general.edit') }}
                </span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CORRESPONDENCE_INDEX, ['department' => $petition->department->slug, 'petition' => $petition]) }}">
                {{ __('petition_correspondence.word_templates') }}
            </a>
        @endcan

        @if ($petition->message !== null && $petition->date_of_message !== null)
            <dl class="description-list">
                <div class="description-list__item">
                    <dt>{{ __('petition_correspondence.message') }}</dt>
                    <dd>{{ $petition->message }}</dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('petition_correspondence.date_of_message') }}</dt>
                    <dd>{{ DisplayDate::date($petition->date_of_message) }}</dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('petition_correspondence.decision_reference') }}</dt>
                    <dd>{{ $petition->decision_reference ?? '-' }}</dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('petition_correspondence.decision_date') }}</dt>
                    <dd>{{ $petition->decision_date ? DisplayDate::date($petition->decision_date) : '-' }}</dd>
                </div>
            </dl>
        @else
            <p>{{ '-' }}</p>
        @endif
    </div>
</div>
