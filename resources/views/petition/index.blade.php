@use(App\Enums\Ability)
@use(App\Enums\ArchiveFilter)
@use(App\Enums\ContactType)
@use(App\Enums\PetitionCriteria)
@use(App\Enums\PetitionTypeType)
@use(App\Enums\RouteName)
@use(App\Enums\StatusGroup)
@use(App\ValueObjects\CalendarDate)
@use(Illuminate\Support\Str)
@use(App\Models\Petition)

@section("pageTitle", __("petition.all_petitions") . " " . ActiveDepartment::getActiveDepartment()?->name)

<x-app-layout>
    <x-slot name="header">
        <div class="action-bar">
            <h1>{{ __("petition.model_plural") }}</h1>
            @can(Ability::CREATE, Petition::class)
                @foreach ($petitionTypes as $petitionType)
                    <a
                        class="button"
                        id="petitions-create-{{ $petitionType->type }}"
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CREATE, ["department" => $department, "petitionType" => $petitionType]) }}"
                        aria-label="{{ $petitionType->name }} {{ Str::lower(__("general.create")) }}">
                        {{ $petitionType->name }}
                        <x-tabler-plus
                            aria-hidden="true"
                            focusable="false" />
                    </a>
                @endforeach
            @endcan
        </div>
    </x-slot>

    <section class="mt-5">
        <div class="visually-grouped filters">
            <form
                data-auto-submit="form"
                method="post"
                class="filters__form"
                action="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER, ["department" => $department]) }}">
                @csrf
                <div class="form-filter-group search">
                    <x-input-label
                        class="form-label"
                        for="search"
                        :content="__('general.search_petitions')" />
                    <div>
                        <input
                            maxlength="255"
                            id="search"
                            class="search__input"
                            type="search"
                            name="filter[{{ PetitionCriteria::SEARCH->value }}]"
                            placeholder="{{ __("general.search_by_multiple") }}"
                            value="{{ request(sprintf("filter.%s", PetitionCriteria::SEARCH->value)) }}" />
                        <button
                            formaction="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER, ["department" => $department]) }}"
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
                    data-hide-table-column-content="{{ strtolower(__("petition.status")) }}">
                    <x-input-label
                        class="form-label"
                        for="petition-status-group"
                        :content="__('petition.status')" />
                    <select
                        data-auto-submit="input"
                        id="petition-status-group"
                        name="filter[{{ PetitionCriteria::STATUS_GROUP->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach (StatusGroup::cases() as $status_group)
                            <option
                                value="{{ $status_group }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::STATUS_GROUP->value)) === $status_group->value)>
                                {{ __("petition_status." . $status_group->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("petition.substatus")) }}">
                    <x-input-label
                        class="form-label"
                        for="petition-status"
                        :content="__('petition.substatus')" />
                    <select
                        data-auto-submit="input"
                        id="petition-status"
                        name="filter[{{ PetitionCriteria::STATUS->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach ($usedPetitionStatuses as $status)
                            <option
                                value="{{ $status->status }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::STATUS->value)) === $status->status)>
                                {{ $status->status }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($usedCustomProperties->isNotEmpty())
                    <div
                        class="form-filter-group"
                        data-hide-table-column-content="{{ strtolower(__("petition.custom_property")) }}">
                        <x-input-label
                            class="form-label"
                            for="petition-custom-property"
                            :content="__('petition.custom_property')" />
                        <select
                            data-auto-submit="input"
                            id="petition-custom-property"
                            name="filter[{{ PetitionCriteria::CUSTOM_PROPERTY->value }}]"
                            class="form-select">
                            <option value="">{{ __("petition.filter.no_filter") }}</option>
                            @foreach ($usedCustomProperties as $property)
                                <option
                                    value="{{ $property->id }}"
                                    @selected(request(sprintf("filter.%s", PetitionCriteria::CUSTOM_PROPERTY->value)) === $property->id->toString())>
                                    {{ $property->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("contact.attached_applicant_type")) }}">
                    <x-input-label
                        class="form-label"
                        for="petition-applicant-type"
                        :content="__('contact.attached_applicant_type')" />
                    <select
                        data-auto-submit="input"
                        id="petition-applicant-type"
                        name="filter[{{ PetitionCriteria::APPLICANT->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach (ContactType::cases() as $contact_type)
                            <option
                                value="{{ $contact_type->value }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::APPLICANT->value)) === $contact_type->value)>
                                {{ __("contact.contact_type_enum." . $contact_type->value) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("policy_department.model_plural")) }}">
                    <x-input-label
                        class="form-label"
                        for="policy-department-id"
                        :content="__('policy_department.model_singular')" />
                    <select
                        data-auto-submit="input"
                        id="policy-department-id"
                        name="filter[{{ PetitionCriteria::POLICY_DEPARTMENT->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach ($policyDepartments as $policyDepartment)
                            <option
                                value="{{ $policyDepartment->id }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::POLICY_DEPARTMENT->value)) === $policyDepartment->id->toString())>
                                {{ $policyDepartment->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("petition_category.model_singular")) }}">
                    <x-input-label
                        class="form-label"
                        for="petition-category-id"
                        :content="__('petition_category.model_singular')" />
                    <select
                        data-auto-submit="input"
                        id="petition-category-id"
                        name="filter[{{ PetitionCriteria::CATEGORY->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach ($usedPetitionCategories as $petitionCategory)
                            <option
                                value="{{ $petitionCategory->id }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::CATEGORY->value)) === $petitionCategory->id->toString())>
                                {{ $petitionCategory->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("petition.type")) }}">
                    <x-input-label
                        class="form-label"
                        for="petition-type"
                        :content="__('petition.type')" />
                    <select
                        data-auto-submit="input"
                        id="petition-type"
                        name="filter[{{ PetitionCriteria::PETITION_TYPE->value }}]"
                        class="form-select">
                        <option value="">{{ __("petition.filter.no_filter") }}</option>
                        @foreach ($usedPetitionTypes as $petitionType)
                            <option
                                value="{{ $petitionType->id }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::PETITION_TYPE->value)) === $petitionType->id->toString())>
                                {{ $petitionType->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-hide-table-column-content="{{ strtolower(__("petition.assigned_user")) }}">
                    <x-input-label
                        class="form-label"
                        for="assigned-user"
                        :content="__('petition.assigned_user')" />
                    <select
                        data-auto-submit="input"
                        id="assigned-user"
                        name="filter[{{ PetitionCriteria::ASSIGNED_USER->value }}]"
                        class="form-select">
                        <option value="">
                            {{ __("petition.filter.no_filter") }}
                        </option>
                        <option
                            value="none"
                            @selected(request()->has(sprintf("filter.%s", PetitionCriteria::ASSIGNED_USER->value)) && request(sprintf("filter.%s", PetitionCriteria::ASSIGNED_USER->value)) === "none")>
                            {{ __("petition.filter.unassigned") }}
                        </option>
                        @foreach ($assignedUsers as $user)
                            <option
                                value="{{ $user->id }}"
                                @selected(request(sprintf("filter.%s", PetitionCriteria::ASSIGNED_USER->value)) === $user->id->toString())>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    class="form-filter-group"
                    data-filter-group="archive">
                    <x-input-label
                        class="form-label"
                        for="filter_archive"
                        :content="__('petition.filter.archive.label')" />
                    <select
                        data-auto-submit="input"
                        id="filter_archive"
                        name="{{ sprintf("filter[%s]", PetitionCriteria::ARCHIVE->value) }}"
                        class="form-select">
                        @foreach ($archiveFilters as $archiveFilter)
                            <option
                                value="{{ $archiveFilter->value }}"
                                @selected(
                                    request(sprintf("filter.%s", PetitionCriteria::ARCHIVE->value)) ===
                                        $archiveFilter->value ||
                                    (! request()->has(sprintf("filter.%s", PetitionCriteria::ARCHIVE->value)) &&
                                        $archiveFilter === ArchiveFilter::HIDE_ARCHIVED)
                                )>
                                {{ $archiveFilter->label() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <button
                    class="form-filter-submit"
                    formaction="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER, ["department" => $department]) }}"
                    data-auto-submit="submit"
                    type="submit"
                    method="post">
                    {{ __("general.filter_now") }}
                </button>

                <a
                    class="button form-filter-reset"
                    @if ($hasSavedFilters)
                        href="{{ route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ["department" => $department, "filter" => "clear"]) }}"
                    @else
                        aria-disabled="true"
                    @endif>
                    {{ __("general.clear_filters") }}
                </a>
            </form>
        </div>
    </section>

    <section class="mt-5">
        <div class="visually-grouped">
            <details
                id="column-hide-button"
                class="column-filter mb-4"
                hidden>
                <summary>
                    <span>
                        {{ __("general.columns_show_hide") }}
                        <x-tabler-chevron-down
                            class="icon"
                            aria-hidden="true"
                            focusable="false" />
                    </span>
                </summary>
                <div
                    class="column-filter__wrapper mt-3"
                    data-hide-table-columns-ui></div>
            </details>
            <div class="x-scrollable-wrapper">
                <div class="shadow shadow-left"></div>
                <div class="shadow shadow-right"></div>
                <div class="x-scrollable">
                    <table data-hide-table-columns="true">
                        <caption class="visually-hidden">{{ __("petition.overview") }}</caption>
                        <thead>
                            <tr>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::NUMBER) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::NUMBER) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.number") }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    data-hide-table-column-content="{{ strtolower(__("petition.description")) }}">
                                    {{ __("petition.description") }}
                                </th>

                                <th
                                    scope="col"
                                    data-hide-table-column-content="{{ strtolower(__("contact.attached_applicant")) }}">
                                    {{ __("contact.attached_applicant") }}
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::STATUS_GROUP) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.status")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::STATUS_GROUP) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.status") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::STATUS) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.substatus")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::STATUS) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.substatus") }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::APPLICANT) }}"
                                    data-hide-table-column-content="{{ strtolower(__("contact.attached_applicant_type")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::APPLICANT) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("contact.attached_applicant_type") }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    data-hide-table-column-content="{{ strtolower(__("policy_department.model_plural")) }}">
                                    {{ __("policy_department.model_plural") }}
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::CATEGORY) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition_category.model_singular")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::CATEGORY) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition_category.model_singular") }}
                                        <x-sort-icon />
                                    </a>
                                </th>

                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::NAME) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.name")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::NAME) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.name") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::PETITION_TYPE) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.type")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::PETITION_TYPE) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.type") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::ASSIGNED_USER) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.assigned_user")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::ASSIGNED_USER) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.assigned_user") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    data-hide-table-column-content="{{ strtolower(__("petition.particularities")) }}">
                                    {{ __("petition.particularities") }}
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::DEADLINE_AT) }}"
                                    data-hide-table-column-content="{{ strtolower(__("petition.deadline_at")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::DEADLINE_AT) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("petition.deadline_at") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::SUM_OF_PENALTIES_PER_DATE) }}"
                                    data-hide-table-column-content="{{ strtolower(__("term.sum_of_penalties_per_date")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::SUM_OF_PENALTIES_PER_DATE) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("term.sum_of_penalties_per_date") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th
                                    scope="col"
                                    aria-sort="{{ Sort::getAria(PetitionCriteria::PENALTY_TO_DATE) }}"
                                    data-hide-table-column-content="{{ strtolower(__("term.penalty_to_date")) }}">
                                    <a
                                        class="sort-link"
                                        href="{{ Sort::getLink(PetitionCriteria::PENALTY_TO_DATE) }}">
                                        <span class="visually-hidden">{{ __("general.sort_by") }}</span>
                                        {{ __("term.penalty_to_date") }}
                                        <x-sort-icon />
                                    </a>
                                </th>
                                <th scope="col">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($petitions as $petition)
                                <tr class="table-row-clickable">
                                    <th scope="row">
                                        {{ $petition->number }}
                                    </th>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.description")) }}">
                                        @if ($petition->description)
                                            {{ $petition->description }}
                                        @else
                                            {{ "-" }}
                                        @endif
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("contact.attached_applicant")) }}">
                                        @if ($petition->applicant->isNotEmpty())
                                            {{ $petition->applicant->first()->display_name }}
                                        @else
                                            {{ "-" }}
                                        @endif
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.status")) }}">
                                        <x-status-badge
                                            badge_type="{{ $petition->petitionStatus->status_group->value }}">
                                            {{ __("petition_status." . $petition->petitionStatus->status_group->value) }}
                                        </x-status-badge>
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.substatus")) }}">
                                        <x-substatus-badge bg_color="{{ $petition->petitionStatus->bg_color }}">
                                            {{ $petition->petitionStatus->status }}
                                        </x-substatus-badge>
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("contact.attached_applicant_type")) }}">
                                        @if ($petition->applicant->isNotEmpty())
                                            {{ __(sprintf("contact.contact_type_enum.%s", $petition->applicant->first()->type->value)) }}
                                        @else
                                            {{ "-" }}
                                        @endif
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("policy_department.model_plural")) }}">
                                        {{ $petition->policyDepartments->toString() }}
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("petition_category.model_singular")) }}">
                                        {{ $petition->petitionCategory?->name }}
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.name")) }}">
                                        {{ $petition->name }}
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.type")) }}">
                                        {{ $petition->petitionType->name }}
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("petition.assigned_user")) }}">
                                        {{ $petition->assignedUser?->name ?? __("petition.not_assigned") }}
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("petition.particularities")) }}">
                                        <x-petition.particularity_labels :petition="$petition" />
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("petition.deadline_at")) }}">
                                        {{ $petition->deadline_at !== null ? DisplayDate::date($petition->deadline_at) : "-" }}
                                    </td>
                                    <td
                                        data-hide-table-column-content="{{ strtolower(__("term.sum_of_penalties_per_date")) }}">
                                        {{ Number::currency($petition->sum_of_penalties_per_date, "EUR", "nl", 0) }}
                                    </td>
                                    <td data-hide-table-column-content="{{ strtolower(__("term.penalty_to_date")) }}">
                                        {{ Number::currency($petition->penalty_to_date, "EUR", "nl", 0) }}
                                    </td>
                                    <td>
                                        <a
                                            class="icon-only"
                                            href="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ["department" => $department, "petition" => $petition]) }}"
                                            aria-label="{{ __("petition.model_singular") }} {{ $petition->number }} {{ __("general.view") }}">
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td
                                        colspan="12"
                                        class="no-results">
                                        @if (! empty(request("number")) || ! empty(request("petition_status_text")) || ! empty(request("assigned_to")) || ! empty(request("petition_type_id")))
                                            {{ __("petition.no_petitions_for_search") }}
                                        @else
                                            {{ __("petition.no_petitions") }}
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>
                                    {{ __("petition.count", ["count" => $petitionCount]) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            {{ $paginator->links() }}
        </div>
    </section>
</x-app-layout>
