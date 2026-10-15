@use(Illuminate\Support\Arr)

@section("pageTitle", __("tag.edit_on_petition"))

<div class="petition-property__block petition-property__block--active">
    @ifHtmx
        <header class="petition-property__header">
            <h2 class="petition-property__title">{{ __("tag.model_plural") }}</h2>

            <a
                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                class="icon-only petition-property__edit"
                hx-push-url="false"
                hx-swap="innerHTML"
                hx-target="#tags-block">
                <x-tabler-settings
                    aria-hidden="true"
                    focusable="false" />
                <span class="visually-hidden">{{ __("tag.edit_on_petition") }}</span>
            </a>
        </header>
    @endifHtmx

    <div class="petition-property__content">
        <form
            method="post"
            action="{{ route(RouteName::DEPARTMENTS_PETITIONS_TAGS_EDIT, ["department" => $petition->department->slug, "petition" => $petition]) }}"
            hx-push-url="false"
            hx-swap="innerHTML"
            hx-target="#tags-block">
            @csrf
            <h3 class="form-section__title form-section__title--border">
                {{ __("tag.edit_on_petition") }}
            </h3>
            <div class="form-input-group">
                <x-input-error
                    id="tags-error"
                    :messages="Arr::flatten($errors->get('tag_ids*'))" />
                @forelse ($tags as $tag)
                    @php
                        $hints = array_filter([
                            "tag-{$tag->id}-description" => $tag->description,
                            "tag-{$tag->id}-hint" => $tag->active ? null : __("tag.inactive_hint"),
                        ]);
                    @endphp

                    <div class="checkbox">
                        <input
                            id="tag-{{ $tag->id }}"
                            type="checkbox"
                            name="tag_ids[]"
                            value="{{ $tag->id->toString() }}"
                            @if ($hints !== [])
                                aria-describedby="{{ implode(" ", array_keys($hints)) }}"
                            @endif
                            @checked($petition->tags->contains("id", $tag->id)) />
                        <x-input-label
                            for="tag-{{ $tag->id }}"
                            :content="$tag->name" />
                    </div>
                    @if ($hints !== [])
                        <p class="nota-bene">
                            @foreach ($hints as $hintId => $hint)
                                @if (! $loop->first)
                                    <br />
                                @endif

                                <span id="{{ $hintId }}">{{ $hint }}</span>
                            @endforeach
                        </p>
                    @endif
                @empty
                    <p>{{ __("tag.no_records") }}</p>
                @endforelse
            </div>
            @if ($errors->any())
                <x-notification type="danger">
                    <p>{{ __("validation.global_message") }}</p>
                </x-notification>
            @endif

            <div class="button-container">
                <x-primary-button>{{ __("general.save") }}</x-primary-button>
                <a
                    class="button"
                    @ifHtmx
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_TAGS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                        hx-target="#tags-block"
                        hx-swap="innerHTML"
                    @else
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $petition->department->slug, "petition" => $petition]) }}"
                    @endifHtmx>
                    {{ __("general.cancel") }}
                </a>
            </div>
        </form>
    </div>
</div>
