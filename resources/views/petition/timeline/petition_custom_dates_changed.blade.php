<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition.custom_date') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                <p>
                    {{
                        $customDatesCollection
                            ->map(static function (array $item): string {
                                $label = __(sprintf('custom_dates.%s', $item['date_label']));
                                $value = $item['date'] ?? __('custom_dates.date_not_set');

                                return sprintf('%s: %s', $label, $value);
                            })
                            ->implode(', ')
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
