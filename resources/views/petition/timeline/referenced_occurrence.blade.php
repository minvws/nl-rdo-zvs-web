@use(App\Enums\Occurrence)
@use(App\Facades\DisplayDate)

<div class="timeline-item timeline-item--occurrence timeline-item--occurrence-reference">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            @if ($timelineItem->data->action === Occurrence::ATTACH_ACTION->value)
                <x-tabler-link
                    aria-hidden="true"
                    focusable="false" />
            @else
                <x-tabler-unlink
                    aria-hidden="true"
                    focusable="false" />
            @endif
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    <span>{{ __($timelineItem->data->type) }}</span>
                    {{ $timelineItem->data->subject }}
                    {{ __($timelineItem->data->action) }}
                </p>
            </x-timeline.timeline-header>
            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
