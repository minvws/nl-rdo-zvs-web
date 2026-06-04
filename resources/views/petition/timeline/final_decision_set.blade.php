@use(App\Facades\DisplayDate)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-flag
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                @if ($timelineItem->data['decision_name'] !== null)
                    {{ __('timeline.final_decision_set.with_decision', ['decision' => $timelineItem->data['decision_name']]) }}
                @else
                    {{ __('timeline.final_decision_set.without_decision') }}
                @endif
            </x-timeline.timeline-header>

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
