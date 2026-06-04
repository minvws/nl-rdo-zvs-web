@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)
@use(App\Enums\OptionalFormFieldSetting)

<div
    class="petition-property__block"
    hx-target="this">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.model_singular') }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#properties-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.model_singular') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        <dl class="description-list description-list--compact">
            <div class="description-list--stacked">
                <dt>{{ __('petition.type') }}</dt>
                <dd>{{ $petition->petitionType->name }}</dd>
            </div>
            @if ($petition->petitionCategory)
                <div class="description-list--item">
                    <dt>{{ __('petition_category.model_singular') }}</dt>
                    <dd>{{ $petition->petitionCategory?->name }}</dd>
                </div>
            @endif

            @if ($petition->team)
                <div class="description-list--item">
                    <dt>{{ __('team.model_singular') }}</dt>
                    <dd>{{ $petition->team->name }}</dd>
                </div>
            @endif

            <div class="description-list--item">
                <dt>{{ __('petition.number') }}</dt>
                <dd>{{ $petition->number }}</dd>
            </div>
            <div class="description-list--item">
                <dt>{{ __('petition.description') }}</dt>
                <dd>{{ $petition->description }}</dd>
            </div>
            <div class="description-list--item">
                <dt>{{ __(sprintf('petition.date_of_entry.%s', $petition->petitionType->type->value)) }}</dt>
                <dd>{{ DisplayDate::date($petition->date_of_entry) }}</dd>
            </div>

            @if ($petitionTypeConfiguration['date_appealed_decision'] !== OptionalFormFieldSetting::EXCLUDED)
                <div class="description-list--item">
                    <dt>
                        {{ __(sprintf('petition.date_appealed_decision.%s', $petition->petitionType->type->value)) }}
                    </dt>
                    <dd>
                        {{ $petition->date_appealed_decision ? DisplayDate::date($petition->date_appealed_decision) : '-' }}
                    </dd>
                </div>
            @endif

            <div class="description-list__item">
                <dt>{{ __('term.days_in_progress') }}</dt>
                <dd>{{ $petition->daysPending }}</dd>
            </div>
            <div class="description-list__item">
                <dt>{{ __('petition.deadline_at') }}</dt>
                <dd>{{ $petition->deadline_at ? DisplayDate::date($petition->deadline_at) : '-' }}</dd>
            </div>
            @if ($petition->isTermEngineConverted())
                <div class="description-list__item">
                    <dt>{{ __('petition.deadline_decision_period') }}</dt>
                    <dd>
                        {{ $petition->deadline_decision_period ? DisplayDate::date($petition->deadline_decision_period) : '-' }}
                    </dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('petition.deadline_notice_of_default') }}</dt>
                    <dd>
                        {{ $petition->deadline_notice_of_default ? DisplayDate::date($petition->deadline_notice_of_default) : '-' }}
                    </dd>
                </div>
                <div class="description-list__item">
                    <dt>{{ __('petition.deadline_appeal_not_timely') }}</dt>
                    <dd>
                        {{ $petition->deadline_appeal_not_timely ? DisplayDate::date($petition->deadline_appeal_not_timely) : '-' }}
                    </dd>
                </div>
                <x-petition.term-exceeded :petition="$petition" />
            @endif
        </dl>
    </div>
</div>
