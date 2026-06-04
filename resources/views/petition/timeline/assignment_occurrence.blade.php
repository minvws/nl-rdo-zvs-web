@use(app\Enums\AssignmentRole)

<div class="timeline-item timeline-item--occurrence timeline-item--occurrence-assignment">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            {{ Str::customInitials($timelineItem->user->name) }}
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    <span>{{ $timelineItem->user->name }}</span>
                    @if ($assignmentRole->value === AssignmentRole::SECONDARY->value)
                        @if ($currentAssignedUser === null)
                            {{ __('timeline.assignment.secondary_dismissed') }}
                        @else
                            {{ __('timeline.assignment.secondary_assigned_to', ['assignee' => $currentAssignedUser->name]) }}
                        @endif
                    @else
                        @if ($currentAssignedUser === null)
                            {{ __('timeline.assignment.dismissed') }}
                        @else
                            {{ __('timeline.assignment.assigned_to', ['assignee' => $currentAssignedUser->name]) }}
                        @endif
                    @endif
                </p>
            </x-timeline.timeline-header>
            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
            </p>
        </div>
    </div>
</div>
