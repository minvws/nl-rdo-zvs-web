<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition.custom_costs') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                <p>
                    {{
                        $customCostsCollection
                            ->map(function ($item) {
                                return __('custom_cost_type.' . $item['custom_cost_type']) . ': ' . Number::currency($item['custom_cost_amount_in_euros'], in: 'EUR', locale: 'nl_NL', precision: 2);
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
