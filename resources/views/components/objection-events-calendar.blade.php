<div class="objection-events-calendar-header">
    <h2 class="mb-3">{{ __('term.objection_events_calendar') }}</h2>
    <button
        id="toggle-event-calendar"
        class="button secondary"
        type="button">
        Klap uit
    </button>
</div>
<table class="objection-events-calendar-table hidden">
    <thead>
        <tr>
            <th>{{ __('term.date') }}</th>
            <th>{{ __('term.event') }}</th>
            <th>{{ __('term.term') }}</th>
            <th>{{ __('term.particularities') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($calendarItems as $day)
            <tr
                class="{{ $day->isDeadline ? 'deadline' : '' }}"
                data-objection-events-calendar="{{ $day->summary() }}"
                data-term="{{ $termText($day) }}"
                data-has-details="{{ $hasEventsOrParticularities($day) ? 'true' : 'false' }}"
                data-applicable-term="{{ $day->applicableTerm ?? '' }}"
                data-events-calendar-date="{{ $day->date->toDateString() }}"
                data-penalty-today-in-euros="{{ $day->penaltyTodayInEuros ?? 0 }}">
                <td class="date">
                    <span>{{ str_replace('.', '', $day->date->translatedFormat('D d M Y')) }}</span>
                </td>
                <td>
                    @foreach ($day->petitionEvents as $event)
                        <div class="{{ $eventClass($event) }}">
                            <span>
                                {{ $eventLabel($event) }}
                            </span>
                        </div>
                    @endforeach
                </td>
                <td class="{{ $termClass($day) }}">
                    <span>
                        {{ $termText($day) }}
                    </span>
                </td>
                <td class="particularities">
                    <span>{{ $day->isATW ? __('term.is_atw') : '' }}</span>
                    <span>{{ $day->isDeadline ? __('term.is_deadline-' . $day->applicableTerm) : '' }}</span>
                    <span>{{ $day->isOriginalDeadline ? __('term.is_original_deadline-decision_period') : '' }}</span>
                    <span>
                        {{ $day->isOpenUnspecifiedAdjournment ? __('term.is_open_unspecified_adjournment') : '' }}
                    </span>
                    <span class="today-penalty"></span>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4">
                    <div class="mt-2">{{ __('term.no_objection_events_calendar_items') }}</div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
