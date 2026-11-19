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

            @if ($events !== [])
                <div class="timeline-item__note">
                    <ul>
                        @foreach ($events as $event)
                            <li>
                                @if ($event->isRemoved())
                                    <del>{{ $event->type->label($petitionType) }}</del>
                                    ({{ __('petition_event.removed') }})
                                @else
                                    {{ $event->type->label($petitionType) }}
                                    @if ($event->isAdded())
                                        ({{ __('petition_event.added') }})
                                    @endif
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }} {{ __('general.by') }} {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
