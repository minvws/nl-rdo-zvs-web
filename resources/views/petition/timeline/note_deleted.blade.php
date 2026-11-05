<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-trash
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header> {{ __('timeline.note_deleted.title') }} </x-timeline.timeline-header>

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }} {{ __('general.by') }} {{ $timelineItem->user->name }}
            </p>

            @if (! empty($timelineItem->data['attachment_names']))
                <p class="fw-bold mt-3">{{ __('timeline.note_deleted.files') }}</p>
                <ul>
                    @foreach ($timelineItem->data['attachment_names'] as $name)
                        <li>{{ $name }}</li>
                    @endforeach
                </ul>
            @endif

            @if (! empty($timelineItem->data['reason']))
                <p class="fw-bold mt-3">{{ __('timeline.note_deleted.reason') }}</p>
                <div class="timeline-item__note">{{ $timelineItem->data['reason'] }}</div>
            @endif
        </div>
    </div>
</div>
