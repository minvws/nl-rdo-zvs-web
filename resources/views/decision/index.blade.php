@use(App\Enums\Ability)
@use(App\Enums\ArchiveFilter)
@use(App\Enums\DecisionCriteria)
@use(App\Enums\RouteName)
@use(App\Models\Decision)

@section('pageTitle', __('decision.all_decisions') . ' ' . ActiveDepartment::getActiveDepartment()?->name)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __('decision.model_plural') }}</h1>
            @can(Ability::CREATE, Decision::class)
                <a
                    class="button"
                    href="{{ route(RouteName::DEPARTMENTS_DECISIONS_CREATE, ['department' => $department]) }}">
                    {{ __('decision.create') }}
                    <x-tabler-plus
                        aria-hidden="true"
                        focusable="false" />
                </a>
            @endcan
        </div>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped filters">
            <form
                data-auto-submit="form"
                method="post"
                class="filters__form"
                action="{{ route(RouteName::DEPARTMENTS_DECISIONS_INDEX_FILTER, ['department' => $department]) }}">
                @csrf
                <div class="form-filter-group search">
                    <x-input-label
                        class="form-label"
                        for="search"
                        :content="__('general.search_decisions')" />
                    <div>
                        <input
                            maxlength="255"
                            id="search"
                            class="search__input"
                            type="search"
                            name="filter[{{ DecisionCriteria::SEARCH->value }}]"
                            placeholder="{{ __('general.search_by_name_or_reference') }}"
                            value="{{ request(sprintf('filter.%s', DecisionCriteria::SEARCH->value)) }}" />
                        <button
                            formaction="{{ route(RouteName::DEPARTMENTS_DECISIONS_INDEX_FILTER, ['department' => $department]) }}"
                            type="submit"
                            class="icon-only">
                            <x-tabler-search
                                aria-hidden="true"
                                focusable="false" />
                            <span class="visually-hidden">Zoeken</span>
                        </button>
                    </div>
                </div>

                <div
                    class="form-filter-group"
                    data-filter-group="archive">
                    <x-input-label
                        class="form-label"
                        for="filter_archive"
                        :content="__('decision.filter.archive.label')" />
                    <select
                        data-auto-submit="input"
                        id="filter_archive"
                        name="{{ sprintf('filter[%s]', DecisionCriteria::ARCHIVE->value) }}"
                        class="form-select">
                        @foreach ($archiveFilters as $archiveFilter)
                            <option
                                value="{{ $archiveFilter->value }}"
                                @selected(
                                    request(sprintf('filter.%s', DecisionCriteria::ARCHIVE->value)) === $archiveFilter->value ||
                                    (! request()->has(sprintf('filter.%s', DecisionCriteria::ARCHIVE->value)) &&
                                        $archiveFilter === ArchiveFilter::HIDE_ARCHIVED)
                                )>
                                {{ $archiveFilter->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-filter-group="type">
                    <x-input-label
                        class="form-label"
                        for="filter_type"
                        :content="__('decision.filter.type.label')" />
                    <select
                        data-auto-submit="input"
                        id="filter_type"
                        name="{{ sprintf('filter[%s]', DecisionCriteria::TYPE->value) }}"
                        class="form-select">
                        <option value="">{{ __('decision.filter.type.all') }}</option>
                        @foreach ($decisionTypes as $decisionType)
                            <option
                                value="{{ $decisionType->value }}"
                                @selected(request(sprintf('filter.%s', DecisionCriteria::TYPE->value)) === $decisionType->value)>
                                {{ $decisionType->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filters__actions">
                    <button
                        formaction="{{ route(RouteName::DEPARTMENTS_DECISIONS_INDEX_FILTER, ['department' => $department]) }}"
                        name="filter"
                        value="clear"
                        type="submit"
                        class="button button--tertiary">
                        {{ __('general.clear_filters') }}
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="mt-5">
        <div class="visually-grouped">
            <div class="x-scrollable-wrapper">
                <div class="shadow shadow-left"></div>
                <div class="shadow shadow-right"></div>
                <div class="x-scrollable">
                    <table>
                        <caption class="visually-hidden">{{ __('decision.overview') }}</caption>
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(DecisionCriteria::NAME) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ $sortHelper->getLink(DecisionCriteria::NAME) }}">
                                        <span class="visually-hidden">{{ __('general.sort_by') }}</span>
                                        {{ __('decision.name') }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ $sortHelper->getAria(DecisionCriteria::DATE) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ $sortHelper->getLink(DecisionCriteria::DATE) }}">
                                        <span class="visually-hidden">{{ __('general.sort_by') }}</span>
                                        {{ __('decision.date') }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ $sortHelper->getAria(DecisionCriteria::REFERENCE) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ $sortHelper->getLink(DecisionCriteria::REFERENCE) }}">
                                        <span class="visually-hidden">{{ __('general.sort_by') }}</span>
                                        {{ __('decision.reference') }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ $sortHelper->getAria(DecisionCriteria::DEADLINE) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ $sortHelper->getLink(DecisionCriteria::DEADLINE) }}">
                                        <span class="visually-hidden">{{ __('general.sort_by') }}</span>
                                        {{ __('decision.deadline') }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ $sortHelper->getAria(DecisionCriteria::PROGRESS) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ $sortHelper->getLink(DecisionCriteria::PROGRESS) }}">
                                        <span class="visually-hidden">{{ __('general.sort_by') }}</span>
                                        {{ __('decision.progress') }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th scope="col">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($decisions as $decision)
                                <tr class="table-row-clickable">
                                    <td>
                                        {{ $decision->name }}
                                    </td>
                                    <td>
                                        {{ $decision->date?->format('d-m-Y') ?? '-' }}
                                    </td>
                                    <td>
                                        {{ $decision->reference ?? '-' }}
                                    </td>
                                    <td>
                                        {{ $decision->processingSteps->deadline()?->format('d-m-Y') ?? '-' }}
                                    </td>
                                    <td>
                                        @if ($decision->processingSteps->isNotEmpty())
                                            {{ $decision->processingSteps->countCompleted() }}
                                            /{{ $decision->processingSteps->countTotal() }}
                                        @else
                                            0/0
                                        @endif
                                    </td>
                                    <td>
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $department, 'decision' => $decision]) }}"
                                            aria-label="{{ __('decision.model_singular') }} {{ $decision->name }} {{ __('general.view') }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="6"
                                        class="text-center">
                                        {{ __('decision.no_decisions_found') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($paginator->hasPages())
                    {{ $paginator->links() }}
                @endif
            </div>
        </div>
    </section>
</x-app-layout>
