@section("pageTitle", __("petition.edit_custom_date"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.custom_date") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#custom-dates-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.custom_date") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            id="custom-dates-form"
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, ["department" => $petition->department, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#custom-dates-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="custom-dates-block" />
            <h3 class="form-section__title form-section__title--border">{{ __("petition.edit_custom_date") }}</h3>
            @foreach ($custom_dates as $index => $customDates)
                <div class="form-input-group">
                    <x-input-label
                        for="custom-dates-{{ $index }}"
                        :content="__('custom_dates.' . $customDates->date_label->value)" />
                    <div class="form-input-group__wrapper">
                        <x-text-input
                            class="form-control"
                            id="custom-dates-{{ $index }}"
                            maxlength="255"
                            type="hidden"
                            name="custom_dates[{{ $index }}][date_label]"
                            value="{{ old('custom_dates.' . $index . '.reference', $customDates->date_label->value) }}" />
                    </div>
                    <div class="form-input-group__wrapper">
                        <input
                            class="form-control @error("custom_dates.0.date") input-error @enderror"
                            type="date"
                            id="custom-dates-date-{{ $index }}"
                            Bsc0HTOvz6RnezuMYaqffBE2af3OFeDe5nr8Jjfgf8zOSMkZrEEGtmAq5t5uAcplB
                            aria-describedby="date-required"
                            name="custom_dates[{{ $index }}][date]"
                            value="{{ old("custom_dates." . $index . ".date", $customDates->date) }}" />
                    </div>
                </div>
            @endforeach

            @if ($errors->any())
                <x-notification type="danger">
                    <p id="date_required">{{ __("validation.date_required") }}</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                        hx-target="#custom-dates-block"
                        hx-swap="innerHTML"
                    @elseifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
