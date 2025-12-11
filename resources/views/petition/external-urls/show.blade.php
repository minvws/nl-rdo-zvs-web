@use(App\Enums\Ability)
@use(App\Enums\RouteName)

@section('pageTitle', __('petition.external_urls'))

<div class="petition-property__block">
    <header class="petition-property__header">
        <h2 class="petition-property__title">{{ __('petition.external_urls') }}</h2>
        @can(Ability::UPDATE, $petition)
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, ['department' => $department, 'petition' => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#external-urls-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __('petition.external_urls') }} {{ __('general.edit') }}</span>
            </a>
        @endcan
    </header>
    <div class="petition-property__content">
        @if ($petition->externalUrls->isEmpty())
            <p>{{ '-' }}</p>
        @else
            <dl class="property-list">
                @foreach ($petition->externalUrls as $externalUrl)
                    <div class="property-list__item">
                        <dt class="property-list__term">
                            {{ __('external_url_type.' . $externalUrl->petition_external_url_type->value) }}
                        </dt>
                        <dd class="property-list__description">
                            <a
                                href="{{ $externalUrl->url }}"
                                target="_blank">
                                {{ $externalUrl->url }}
                            </a>
                        </dd>
                    </div>
                @endforeach
            </dl>
        @endif
    </div>
</div>
