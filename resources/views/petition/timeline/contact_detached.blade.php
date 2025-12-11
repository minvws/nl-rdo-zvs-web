<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-unlink
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    {{
                        __('timeline.contact_detached.body', [
                            'contact' => $contact->initials . ' ' . $contact->last_name,
                            'role' => __('contact.' . $timelineItem->data->role),
                        ])
                    }}
                </p>
            </x-timeline.timeline-header>
            <div class="timeline-item__note"></div>
            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
