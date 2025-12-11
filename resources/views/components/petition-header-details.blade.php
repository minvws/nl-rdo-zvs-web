<div class="petition-details">
    @if ($hasBackLink)
        <a
            class="petition-details__link button mb-4"
            href="{{ $backLinkRoute }}">
            <x-tabler-chevron-left
                aria-hidden="true"
                focusable="false" />
            {{ $backLinkLabel }}
        </a>
    @endif

    @if ($petition->archived_at)
        <x-status-badge badge_type="danger">
            {{ $petition->petitionType->name }} ({{ __('petition.archived') }})
        </x-status-badge>
    @else
        <x-status-badge badge_type="info">{{ $petition->petitionType->name }}</x-status-badge>
    @endif
    <h1 class="petition-details__header">
        @if ($petition->applicant->isNotEmpty())
            {{ $petition->applicant->first()->display_name }}
        @else
            {{ __('contact.no_applicant') }}
        @endif

        <span class="petition-details__title">{{ $petition->name }}</span>
    </h1>
    <span class="petition-details__number">{{ $petition->number }}</span>
</div>
