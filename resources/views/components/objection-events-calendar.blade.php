<div class="objection-events-calendar-header">
    <h2 class="mb-3">{{ __('term.objection_events_calendar') }}</h2>
    <button
        id="toggle-event-calendar"
        class="mb-3"
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
                data-applicable-term="{{ $day->applicableTerm ?? '' }}"
                data-events-calendar-date="{{ $day->date->toDateString() }}"
                data-penalty-today-in-euros="{{ $day->penaltyTodayInEuros ?? 0 }}">
                <td class="date">
                    <span>{{ str_replace('.', '', $day->date->translatedFormat('D d M Y')) }}</span>
                </td>
                <td>
                    @foreach ($day->petitionEvents as $event)
                        <div class="event-type event-type--{{ $event->type->value }}">
                            <span>
                                {{
                                    __(
                                        'term.' .
                                            $event->type->value .
                                            ($event->suspensionType ? '.' . $event->suspensionType->value : '') .
                                            ($event->resultType ? '.' . $event->resultType->value : ''),
                                    )
                                }}
                            </span>
                        </div>
                    @endforeach
                </td>
                <td
                    class="term-type {{ $day->applicableTerm ? ' term-type--' . $day->applicableTerm : '' }}{{ $day->suspensionType ? '__suspended' : '' }}">
                    <span>
                        {{ $day->applicableTerm ? __('term.' . $day->applicableTerm . '.' . ($day->suspensionType ? $day->suspensionType->value : 'default')) : '' }}{{ $day->penaltyTodayInEuros ? ': €' . $day->penaltyTodayInEuros : '' }}
                    </span>
                </td>
                <td class="particularities">
                    <span>{{ $day->isATW ? __('term.is_atw') : '' }}</span>
                    <span>{{ $day->isDeadline ? __('term.is_deadline-' . $day->applicableTerm) : '' }}</span>
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
