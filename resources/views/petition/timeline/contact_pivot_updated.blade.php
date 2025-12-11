@use(App\Facades\DisplayDate)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-pencil
                aria-hidden="true"
                focusable="false" />
        </div>
        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                <p>
                    {{
                        __('timeline.contact_pivot_updated.body', [
                            'contact' => $contact->initials . ' ' . $contact->last_name,
                        ])
                    }}
                </p>
            </x-timeline.timeline-header>
            <div class="timeline-item__note">
                @if (! empty($timelineItem->data->reference))
                    <p>
                        <strong>{{ __('contact.reference') }}:</strong>
                        {{ $timelineItem->data->reference }}
                    </p>
                @endif

                @if (! empty($timelineItem->data->correspondence_preference))
                    <p>
                        <strong>{{ __('contact.correspondence_preference') }}:</strong>
                        {{ $timelineItem->data->correspondence_preference }}
                    </p>
                @endif
            </div>
            <p class="timeline-item__meta">
                {{ ucfirst(__('general.on')) }} {{ DisplayDate::sentence($timelineItem->created_at) }}
                {{ __('general.by') }}
                {{ $timelineItem->user->name }}
            </p>
        </div>
    </div>
</div>
