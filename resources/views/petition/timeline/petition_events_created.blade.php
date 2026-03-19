@use(App\Enums\PetitionEventType)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-calendar-event
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition_event.calendar_changed') }} {{ __('petition_event.events') }}
            </x-timeline.timeline-header>

            @if ($timelineItem->data?->event_types && is_array($timelineItem->data->event_types))
                <div class="timeline-item__note">
                    <ul>
                        @foreach ($timelineItem->data->event_types as $eventType)
                            <li>
                                @php
                                    $eventType = PetitionEventType::from($eventType);
                                    $petitionType = $timelineItem->timelineable?->petitionType->type;
                                @endphp

                                {{ $eventType->label($petitionType) }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
