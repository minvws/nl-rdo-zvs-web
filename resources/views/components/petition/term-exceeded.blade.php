@use(App\Models\Petition)
@use(App\ValueObjects\CalendarDate)

@props([
    'petition' => Petition::class,
])

@php
    $deadline = $petition->deadline_decision_period;
    $today = CalendarDate::create('today');
@endphp

@if ($deadline)
    @if ($deadline->isInThePast())
        <div class="description-list__item">
            <dt>{{ __('petition.days_outside_legal_term') }}</dt>
            <dd>{{ abs($today->diffInDays($deadline)) }}</dd>
        </div>
    @else
        <div class="description-list__item">
            <dt>{{ __('petition.days_within_legal_term') }}</dt>
            <dd>{{ abs($deadline->diffInDays($today)) }}</dd>
        </div>
    @endif
@endif
