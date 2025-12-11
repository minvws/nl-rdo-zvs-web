@use(App\Enums\Ability)
@use(App\Enums\RouteName)
@use(App\Enums\Authorization\Permission)
@use(Illuminate\Support\Facades\Crypt)

@section('pageTitle', __('decision.detail_page') . ' ' . $decision->name)

<x-app-layout>
    <div class="container mt-3">
        <div class="grid">
            <div class="visually-grouped">
                <section
                    id="decision-header"
                    class="petition-header"
                    hx-trigger="eventPetitionUpdated-header from:body"
                    hx-get="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]) }}"
                    hx-select="#decision-header"
                    hx-swap="outerHTML"
                    aria-live="polite">
                    <div class="petition-details">
                        @if ($decision->archived_at)
                            <x-status-badge badge_type="danger">
                                {{ __('decision.model_singular') }} ({{ __('decision.archived') }})
                            </x-status-badge>
                        @else
                            <x-status-badge badge_type="info">{{ __('decision.model_singular') }}</x-status-badge>
                        @endif
                        <h1 class="petition-details__header">{{ $decision->name }}</h1>
                        <span class="petition-details__number">{{ $decision->reference }}</span>
                    </div>
                </section>

                <x-petition.table
                    :title="__('decision.attached_petitions')"
                    :petitions="$decision->petitions"
                    :detachUrl="$decision->archived_at === null ? fn ($detachPetition) => confirmRoute(
                        route(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, [ 'department' => $detachPetition->department, 'petition' => $detachPetition, 'relatedDecision' => $decision->id, 'referer' => 'decision' ]),
                        route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]),
                        __('decision.detach_confirmation_text', ['decision' => $decision->name, 'zaak' => $detachPetition->number]),
                    ) : null">
                    >
                    <x-slot:footer>
                        @can(Ability::UPDATE, [$decision])
                            @if ($decision->archived_at === null)
                                <div class="button-container">
                                    <a
                                        href="
                                    {{ route(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH_FORM, ['department' => $decision->department, 'decision' => $decision]) }}
                                    ">
                                        {{ __('decision.attach_petition') }}
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </x-slot>
                </x-petition.table>

                <x-decision.processing-step.table
                    :title="__('decision.processing_steps')"
                    :decision="$decision"
                    :processingSteps="$decision->processingSteps">
                    <x-slot:footer>
                        @can(Ability::UPDATE, [$decision])
                            @if ($decision->archived_at === null)
                                <div class="button-container">
                                    <a
                                        href="{{ route(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_CREATE, ['department' => $decision->department, 'decision' => $decision]) }}
                                    ">
                                        {{ __('processing-step.add_processing_step') }}
                                    </a>
                                </div>
                            @endif
                        @endcan
                    </x-slot>
                </x-decision.processing-step.table>

                <section class="mt-5">
                    <h2 class="mb-3">{{ __('petition.updates') }}</h2>
                    <div>
                        <div
                            class="timeline-actions mb-2"
                            hx-boost="true">
                            @can(Ability::UPDATE, [$decision])
                                @if ($decision->archived_at === null)
                                    <a
                                        class="button"
                                        href="{{
                                            route(RouteName::DEPARTMENTS_TIMELINEABLE_NOTES_CREATE, [
                                                'department' => $decision->department,
                                                'timelineableType' => $decision->getMorphClass(),
                                                'timelineable' => $decision->id,
                                                'url' => Crypt::encryptString(
                                                    route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                                                        'department' => $decision->department,
                                                        'decision' => $decision,
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
                                @endif
                            @endcan

                            <form
                                id="filter-form"
                                data-auto-submit="form"
                                hx-trigger="change from:select[name='timeline_filter_group']"
                                hx-get="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]) }}"
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
                                    formaction="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]) }}"
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
                        hx-get="{{ route(RouteName::DEPARTMENTS_DECISIONS_SHOW, ['department' => $decision->department, 'decision' => $decision]) }}"
                        hx-select="#timeline-container"
                        hx-swap="outerHTML"
                        aria-live="polite">
                        @foreach ($decisionTimelineItems as $timelineItem)
                            <x-timeline-item :timelineItem="$timelineItem" />
                        @endforeach
                    </div>
                </section>
            </div>

            <x-decision.sidebar :decision="$decision" />
        </div>
    </div>
</x-app-layout>
