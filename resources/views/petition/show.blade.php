@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Facades\DisplayDate)
@use(Illuminate\Support\Facades\Crypt)

@section('pageTitle', __('petition.detail_page') . ' ' . $petition->name)

<x-app-layout>
    <div class="container mt-3">
        <div class="grid">
            <div class="visually-grouped">
                <div
                    class="petition-header"
                    hx-trigger="eventPetitionUpdated-header from:body"
                    hx-get="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}"
                    hx-select=".petition-header"
                    hx-swap="outerHTML"
                    aria-live="polite">
                    <x-petition-header-details
                        :petition="$petition"
                        :hasBackLink="false"
                        :backLinkRoute="route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition])"
                        :backLinkLabel="__('general.back_to_petition')" />
                    <div
                        class="petition-header__status"
                        hx-trigger="eventPetitionUpdated-status from:body"
                        hx-get="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}"
                        hx-select=".petition-header__status"
                        hx-swap="innerHTML">
                        @can(Ability::UPDATE, $petition)
                            <a
                                class="button button--status-trigger"
                                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, ['department' => $department, 'petition' => $petition]) }}">
                                <x-substatus-badge bg_color="{{ $petition->petitionStatus->bg_color }}">
                                    {{ $petition->petitionStatus->status }}
                                </x-substatus-badge>
                                <x-tabler-chevron-down
                                    aria-hidden="true"
                                    focusable="false" />
                            </a>
                        @endcan
                    </div>
                </div>

                @if ($petition->isTermEngineConverted())
                    <x-objection-events-calendar :events="$events" />
                    @if (Config::get('app.features.term_engine_v2'))
                        <a
                            class="mt-2"
                            href="{{ route(RouteName::PETITION_EVENTS_WIZARD_RESET, ['department' => $petition->department, 'petition' => $petition]) }}">
                            {{ __('term.manage_terms') }}
                        </a>
                    @endif
                @endif

                <div class="{{ ! $petition->isTermEngineConverted() ? '' : 'mt-5 hidden' }}">
                    <x-petition.petition-terms.table
                        :petition="$petition"
                        :petitionTerms="$petition->petitionTerms"
                        :draftTerm="$petition->draftTerm"
                        :departmentSlug="$department->slug" />
                </div>

                <x-petition.petition-deliverables.table :petition="$petition" />

                <section class="mt-5">
                    <h2>{{ __('decision.model_plural') }}</h2>
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">{{ __('decision.name') }}</th>
                                <th scope="col">{{ __('decision.date') }}</th>
                                <th scope="col">{{ __('decision.reference') }}</th>
                                <th scope="col">{{ __('decision.deadline') }}</th>
                                <th scope="col">{{ __('decision.finished') }}</th>
                                <th
                                    class="action-column"
                                    scope="col">
                                    &nbsp;
                                </th>
                                <th
                                    class="action-column"
                                    scope="col">
                                    &nbsp;
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($petition->decisions as $decision)
                                <tr>
                                    <th scope="row">{{ $decision->name }}</th>
                                    <td>{{ $decision->date ? DisplayDate::date($decision->date) : '' }}</td>
                                    <td>{{ $decision->reference }}</td>
                                    <td>
                                        {{ $decision->processingSteps->deadline() ? DisplayDate::date($decision->processingSteps->deadline()) : '-' }}
                                    </td>
                                    <td>
                                        @if ($decision->processingSteps->isNotEmpty())
                                            {{ $decision->processingSteps->countCompleted() . '/' . $decision->processingSteps->countTotal() }}
                                        @else
                                            {{ '-' }}
                                        @endif
                                    </td>

                                    <td>
                                        <a
                                            class="icon-only"
                                            href="{{
                                                route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                                                    'department' => $decision->department->slug,
                                                    'decision' => $decision,
                                                ])
                                            }}">
                                            <span class="visually-hidden">
                                                {{ __('general.view') }} {{ $decision->name }}
                                            </span>
                                            <x-tabler-chevron-right
                                                aria-hidden="true"
                                                focusable="false" />
                                        </a>
                                    </td>
                                    <td>
                                        @can(Ability::UPDATE, $petition)
                                            <a
                                                class="icon-only"
                                                href="{{
                                                    confirmRoute(
                                                        route(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, ['department' => $department, 'petition' => $petition, 'relatedDecision' => $decision]),
                                                        route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]),
                                                        __('decision.detach_confirmation_text', ['decision' => $decision->name, 'zaak' => $petition->number]),
                                                    )
                                                }}"
                                                aria-label="{{ __('general.view') }}">
                                                <x-tabler-unlink
                                                    aria-hidden="true"
                                                    focusable="false" />
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3">
                                        {{ __('decision.no_records') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">
                                    @can(Ability::UPDATE, $petition)
                                        <div class="button-container">
                                            <a
                                                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_DECISIONS_CREATE, ['department' => $department, 'petition' => $petition]) }}">
                                                {{ __('decision.create') }}
                                            </a>
                                            <a
                                                href="{{ route(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH_FORM, ['department' => $department, 'petition' => $petition]) }}">
                                                {{ __('decision.link') }}
                                            </a>
                                        </div>
                                    @endcan
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </section>

                <x-petition.table
                    :title="__('petition.attached_petitions')"
                    :petitions="$petition->relatedPetitions"
                    :detachUrl="$user->can('update', $petition) ? fn ($relatedPetition) => confirmRoute(
                        route(RouteName::DEPARTMENTS_PETITION_PETITION_DETACH, ['department' => $department, 'petition' => $petition, 'relatedPetition' => $relatedPetition]),
                        route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]),
                        sprintf('Weet u zeker dat u zaak %s wilt loskoppelen van zaak %s?', $relatedPetition->number, $petition->number)
                    ) : null">
                    <x-slot:footer>
                        @can(Ability::UPDATE, $petition)
                            <div class="button-container">
                                <a
                                    href="{{ route(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, ['department' => $department, 'petition' => $petition]) }}">
                                    {{ __('petition.link') }}
                                </a>
                            </div>
                        @endcan
                    </x-slot>
                </x-petition.table>

                <section class="mt-5">
                    <h2 class="mb-3">{{ __('petition.updates') }}</h2>
                    <div>
                        <div
                            class="timeline-actions mb-2"
                            hx-boost="true">
                            @can(Ability::UPDATE, $petition)
                                <a
                                    class="button"
                                    href="{{
                                        route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                                            'department' => $department,
                                            'timelineableType' => $petition->getMorphClass(),
                                            'timelineable' => $petition->id,
                                            'url' => Crypt::encryptString(
                                                route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                                                    'department' => $department,
                                                    'petition' => $petition,
                                                ]),
                                            ),
                                        ])
                                    }}"
                                    hx-push-url="false"
                                    hx-swap="innerHTML"
                                    hx-target="#notes-block">
                                    @csrf
                                    {{ __('petition.note') . ' ' . __('general.add') }}
                                    <x-tabler-message-dots
                                        aria-hidden="true"
                                        focusable="false" />
                                </a>
                            @endcan

                            <form
                                id="filter-form"
                                data-auto-submit="form"
                                hx-trigger="change from:select[name='timeline_filter_group']"
                                hx-get="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}"
                                hx-target="#timeline-container"
                                hx-select="#timeline-container"
                                hx-swap="outerHTML">
                                <label for="timeline_filter_group">
                                    {{ __('timeline.filter_by_group') }}
                                </label>
                                <select
                                    id="timeline_filter_group"
                                    name="timeline_filter_group"
                                    class="form-select">
                                    <option
                                        value="no_selection"
                                        {{ $selectedTimelineFilter === 'no_selection' ? 'selected' : '' }}>
                                        {{ __('timeline.all_timeline_items') }}
                                    </option>
                                    @foreach ($timelineFilterGroups as $value => $label)
                                        <option
                                            value="{{ $value }}"
                                            {{ $selectedTimelineFilter === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <button
                                    formaction="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}"
                                    data-auto-submit="submit"
                                    type="submit"
                                    method="get">
                                    {{ __('general.filter_now') }}
                                </button>
                            </form>
                        </div>
                    </div>

                    <div id="notes-block"></div>

                    <div
                        id="timeline-container"
                        class="timeline"
                        hx-trigger="eventPetitionUpdated-timeline from:body"
                        hx-get="{{ route(RouteName::DEPARTMENTS_PETITIONS_SHOW, ['department' => $department, 'petition' => $petition]) }}"
                        hx-select="#timeline-container"
                        hx-swap="outerHTML"
                        aria-live="polite">
                        @foreach ($petitionTimelineItems as $timelineItem)
                            <x-timeline-item :timelineItem="$timelineItem" />
                        @endforeach
                    </div>
                </section>
            </div>

            <x-petition.sidebar
                :petition="$petition"
                :department="$department"
                :petitionTypeConfiguration="$petitionTypeConfiguration"
                :availableCostTypes="$availableCostTypes" />
        </div>
    </div>
</x-app-layout>
