@use(App\Facades\DisplayDate)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-phone
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    {{
                        __('timeline.contact_first_phone_contact.body', [
                            'contact' => $contact->initials . ' ' . $contact->last_name,
                            'date' => DisplayDate::date($date),
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
