<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition_correspondence.correspondence') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            @if (($timelineItem->data['message'] ?? null) !== null || $date !== null)
                <div class="timeline-item__note">
                    <p>
                        @if (($timelineItem->data['message'] ?? null) !== null)
                                {{ __('petition_correspondence.message') }}: {{ $timelineItem->data['message'] }}
                        @endif

                        @if ($date !== null)
                                {{ __('petition_correspondence.date_of_message') }}: {{ DisplayDate::date($date) }}
                        @endif
                    </p>
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
