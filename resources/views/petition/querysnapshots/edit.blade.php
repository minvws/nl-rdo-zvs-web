@use(App\Enums\RouteName)
@section("pageTitle", __("petition.edit_querysnapshots"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("petition.querysnapshots") }}</h2>
            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#querysnapshots-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("petition.querysnapshots") }} {{ __("general.edit") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            id="querysnapshots-form"
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_UPDATE, ["department" => $petition->department, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#querysnapshots-block">
            @csrf
            <input
                type="hidden"
                name="hx-target"
                value="querysnapshots-block" />
            <h3 class="form-section__title form-section__title--border">{{ __("petition.edit_querysnapshots") }}</h3>
            @foreach ($availableQuerysnapshotTypes as $index => $querysnapshotType)
                <div class="form-input-group">
                    <x-input-label
                        for="querysnapshots-{{ $index }}"
                        :content="__('querysnapshot_type.' . $querysnapshotType)" />

                    <div class="form-input-group__wrapper">
                        <x-text-input
                            class="form-control"
                            id="querysnapshots-{{ $index }}"
                            maxlength="255"
                            type="hidden"
                            name="querysnapshots[{{ $index }}][querysnapshot_type]"
                            value="{{ $querysnapshotType }}" />
                    </div>
                    <div class="form-input-group__wrapper">
                        <input
                            class="form-control @error("querysnapshots.{$index}.querysnapshot_id") input-error @enderror"
                            type="text"
                            id="querysnapshots-querysnapshot_id-{{ $index }}"
                            aria-describedby="querysnapshot_id-input"
                            name="querysnapshots[{{ $index }}][querysnapshot_id]"
                            value="{{ old("querysnapshots.{$index}.querysnapshot_id", $petition->querysnapshots->firstWhere("querysnapshot_type.value", $querysnapshotType)?->querysnapshot_id ?? "") }}" />
                    </div>
                    <div>
                        <x-input-error
                            id="querysnapshots-querysnapshot_id-{{ $index }}"
                            :messages="$errors->get('querysnapshots.'.$index.'.querysnapshot_id')" />
                    </div>
                </div>
            @endforeach

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_QUERYSNAPSHOTS_SHOW, ["department" => $petition->department, "petition" => $petition]) }}"
                        hx-target="#querysnapshots-block"
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
