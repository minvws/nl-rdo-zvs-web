<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition_deliverable.petition_deliverable_type.' . $timelineItem->data->type) }}
                {{ Str::lower(__('general.created')) }}
            </x-timeline.timeline-header>

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
