@section("pageTitle", __("petition.edit_external_urls"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.external_urls") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_SHOW, ["department" => $department, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#external-urls-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.external_urls") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            id="external-urls-form"
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_UPDATE, ["department" => $department, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#external-urls-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="external-urls-block" />
            <h3 class="form-section__title form-section__title--border">{{ __("petition.edit_external_urls") }}</h3>
            @foreach ($availableUrlTypes as $index => $urlType)
                <div class="form-input-group">
                    <x-input-label
                        for="external-urls-{{ $index }}"
                        :content="__('external_url_type.' . $urlType)" />

                    <div class="form-input-group__wrapper">
                        <x-text-input
                            class="form-control"
                            id="external-urls-{{ $index }}"
                            maxlength="255"
                            type="hidden"
                            name="external_urls[{{ $index }}][petition_external_url_type]"
                            value="{{ $urlType }}" />
                    </div>
                    <div class="form-input-group__wrapper">
                        <input
                            class="form-control @error("external_urls.{$index}.url") input-error @enderror"
                            type="url"
                            id="external-urls-url-{{ $index }}"
                            aria-describedby="url-input"
                            name="external_urls[{{ $index }}][url]"
                            value="{{ old("external_urls.{$index}.url", $petition->externalUrls->firstWhere("petition_external_url_type.value", $urlType)?->url ?? "") }}" />
                    </div>
                    <div>
                        <x-input-error
                            id="external-urls-url-{{ $index }}"
                            :messages="$errors->get('external_urls.'.$index.'.url')" />
                    </div>
                </div>
            @endforeach

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_SHOW, ["department" => $department, "petition" => $petition]) }}"
                        hx-target="#external-urls-block"
                        hx-swap="innerHTML"
                    @elseifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $department, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
