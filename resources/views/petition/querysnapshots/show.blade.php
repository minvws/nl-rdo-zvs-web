@use(App\Enums\Ability)
@use(App\Enums\RouteName)

@section('pageTitle', __('petition.querysnapshots'))

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.querysnapshots') }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_EDIT, ['department' => $petition->department->slug, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#querysnapshots-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.querysnapshots') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->querysnapshots->isEmpty())
            <p>{{ '-' }}</p>
        @else
            <dl class="property-list">
                @foreach ($petition->querysnapshots as $querysnapshot)
                    <div class="property-list__item">
                        <dt class="property-list__term">
                            {{ __('querysnapshot_type.' . $querysnapshot->querysnapshot_type->value) }}
                        </dt>
                        <dd class="property-list__description">
                            {{ $querysnapshot->querysnapshot_id }}
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>
