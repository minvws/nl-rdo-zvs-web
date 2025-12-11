@use(App\Facades\DisplayDate)
@use(App\ValueObjects\CalendarDate)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition.model_singular') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                <p>
                    @if (property_exists($timelineItem->data, 'petition_category_id'))
                        {{ __('petition_category.model_singular') }}: {{ $petitionCategory?->name }}
                        <br />
                    @endif

                    @if (property_exists($timelineItem->data, 'name'))
                        {{ __('petition.name') }}: {{ $timelineItem->data['name'] }}
                        <br />
                    @endif

                    {{ __(sprintf('petition.date_of_entry.%s', $petition->petitionType->type->value)) }}
                    :
                    {{ DisplayDate::date(CalendarDate::createFromFormat('Y-m-d', $timelineItem->data['date_of_entry'])) }}
                    <br />
                    @if (property_exists($timelineItem->data, 'description'))
                            {{ __('petition.description') }}: {{ $timelineItem->data['description'] }}
                    @endif
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
