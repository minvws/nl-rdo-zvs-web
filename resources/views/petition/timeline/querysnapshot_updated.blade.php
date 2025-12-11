@use(App\Facades\DisplayDate)

<div class="timeline-item timeline-item--occurrence">
    <div class="timeline-item__wrapper">
        <div class="timeline-item__badge">
            <x-tabler-clock
                aria-hidden="true"
                focusable="false" />
        </div>

        <div class="timeline-item__content">
            <x-timeline.timeline-header>
                {{ __('petition.querysnapshots') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                @if (! empty($querysnapshots))
                    <p>{{ __('petition.querysnapshots') }}:</p>
                    <ul>
                        @foreach ($querysnapshots as $querysnapshot)
                            @if (! empty($querysnapshot['querysnapshot_id']))
                                <li>
                                    {{ __('querysnapshot_type.' . $querysnapshot['querysnapshot_type']) }}:
                                    {{ $querysnapshot['querysnapshot_id'] }}
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <p>{{ __('petition.no_querysnapshots') }}</p>
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
