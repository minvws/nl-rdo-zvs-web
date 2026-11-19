@use (App\Facades\DisplayDate)

<div class="description-list__item">
    <dt>{{ __('petition.deadline_at') }}</dt>
    <dd>{{ $petition->effective_deadline_at ? DisplayDate::date($petition->effective_deadline_at) : '-' }}</dd>
</div>
@if ($petition->hasDeadlineOverride())
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_override_reason') }}</dt>
        <dd>{{ $petition->deadline_override_reason }}</dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_calculated') }}</dt>
        <dd>{{ $petition->deadline_at ? DisplayDate::date($petition->deadline_at) : '-' }}</dd>
    </div>
@endif

@if ($petition->isTermEngineConverted())
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_decision_period') }}</dt>
        <dd>
            {{ $petition->deadline_decision_period ? DisplayDate::date($petition->deadline_decision_period) : '-' }}
        </dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_notice_of_default') }}</dt>
        <dd>
            {{ $petition->deadline_notice_of_default ? DisplayDate::date($petition->deadline_notice_of_default) : '-' }}
        </dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_notice_of_default_penalty_period_end') }}</dt>
        <dd>
            {{ $deadlineNoticeOfDefaultPenaltyPeriodEnd ? DisplayDate::date($deadlineNoticeOfDefaultPenaltyPeriodEnd) : '-' }}
        </dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.igs_forfeited') }}</dt>
        <dd>{{ $petition->igs_forfeited > 0 ? Number::currency($petition->igs_forfeited, 'EUR', 'nl', 0) : '-' }}</dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_appeal_not_timely') }}</dt>
        <dd>
            {{ $petition->deadline_appeal_not_timely ? DisplayDate::date($petition->deadline_appeal_not_timely) : '-' }}
        </dd>
    </div>
    <div class="description-list__item">
        <dt>{{ __('petition.deadline_appeal_not_timely_penalty_period_end') }}</dt>
        <dd>
            {{ $deadlineAppealNotTimelyPenaltyPeriodEnd ? DisplayDate::date($deadlineAppealNotTimelyPenaltyPeriodEnd) : '-' }}
        </dd>
    </div>
    <x-petition.term-exceeded :petition="$petition" />
@endif
