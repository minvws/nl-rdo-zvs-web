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
                {{ __('petition.external_urls') }} {{ Str::lower(__('general.updated')) }}
            </x-timeline.timeline-header>

            <div class="timeline-item__note">
                @if (! empty($externalUrls))
                    <p>{{ __('petition.external_urls') }}:</p>
                    <ul>
                        @foreach ($externalUrls as $externalUrl)
                            @if (! empty($externalUrl['url']))
                                <li>
                                    {{ __('external_url_type.' . $externalUrl['petition_external_url_type']) }}:
                                    <a
                                        href="{{ $externalUrl['url'] }}"
                                        target="_blank"
                                        rel="noopener noreferrer">
                                        {{ $externalUrl['url'] }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                @else
                    <p>{{ __('petition.no_external_urls') }}</p>
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
