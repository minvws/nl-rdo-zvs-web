@use(App\Facades\DisplayDate)
<div class="timeline-item timeline-item--occurrence timeline-item--occurrence-status">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-rotate-2
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    <span>{{ __('occurrence.current_status') }}:</span>
                    {{
                        __('petition.status_changed_to', [
                            'currentStatus' => $currentStatus,
                            'date' => DisplayDate::date($date),
                        ])
                    }}
                    @if ($comment)
                            {{ ' ' }} {{ __('petition.status_changed_with_comment') }} {{ $comment }}
                    @endif
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
