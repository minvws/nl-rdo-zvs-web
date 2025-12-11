@use(App\Enums\ContactCriteria)

<div class="visually-grouped filters">
    <form
        data-auto-submit="form"
        method="post"
        class="filters__form"
        action="{{ $action }}">
        @csrf
        <div class="form-filter-group search">
            <x-input-label
                class="form-label"
                for="search"
                :content="__('general.search_contacts')" />
            <div>
                <input
                    maxlength="255"
                    id="search"
                    class="search__input"
                    type="search"
                    name="filter[{{ ContactCriteria::SEARCH->value }}]"
                    placeholder="{{ __('general.search_by_multiple') }}"
                    value="{{ request(sprintf('filter.%s', ContactCriteria::SEARCH->value)) }}" />
                <button
                    formaction="{{ $action }}"
                    type="submit"
                    class="icon-only">
                    <x-tabler-search
                        aria-hidden="true"
                        focusable="false" />
                    <span class="visually-hidden">Zoeken</span>
                </button>
            </div>
        </div>
    </form>
</div>
