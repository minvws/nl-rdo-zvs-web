@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)

<div
    class="petition-property__block"
    hx-target="this">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ $decision->type->label() }}</h2>
        @can(Ability::UPDATE, [$decision])
            <a
                href="{{ route(RouteName::DEPARTMENTS_DECISIONS_EDIT, ['department' => $decision->department->slug, 'decision' => $decision]) }}"
                class="button button--inverse"
                data-throw-petition-refresh="header">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        <dl class="description-list description-list--compact">
            <div class="description-list--item">
                <dt>{{ __('decision.name') }}</dt>
                <dd>{{ $decision->name }}</dd>
            </div>
            <div class="description-list--item">
                <dt>{{ __('decision.reference') }}</dt>
                <dd>{{ $decision->reference ? $decision->reference : '-' }}</dd>
            </div>
            <div class="description-list--item">
                <dt>{{ __('decision.date') }}</dt>
                <dd>{{ $decision->date ? DisplayDate::date($decision->date) : '-' }}</dd>
            </div>
            <div class="description-list--item">
                <dt>{{ __('decision.reviewbatch') }}</dt>
                <dd>{{ $decision->reviewbatch ? $decision->reviewbatch : '-' }}</dd>
            </div>
            @if (! empty($decision->url))
                <div class="description-list--item">
                    <dt>{{ __('decision.url') }}</dt>
                    <dd><a href="{{ $decision->url }}">{{ $decision->url }}</a></dd>
                </div>
            @endif

            @if ($decision->team)
                <div class="description-list--item">
                    <dt>{{ __('team.model_singular') }}</dt>
                    <dd>{{ $decision->team->name }}</dd>
                </div>
            @endif
        </dl>
    </div>
</div>
