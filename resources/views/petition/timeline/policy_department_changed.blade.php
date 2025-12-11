<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('policy_department.model_plural') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                <p>
                    {{
                        __('timeline.policy_department_changed', [
                            'policyDepartments' => $policyDepartments,
                        ])
                    }}
                </p>
            </div>

            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
