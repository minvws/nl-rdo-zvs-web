<?php

declare(strict_types=1);

namespace App\Enums;

use function in_array;

enum TermType: string
{
    case FIRST = 'first';
    case SECOND = 'second';
    case THIRD = 'appointment_with_applicant';
    case SUSPENSION = 'suspension';
    case NOTICE_OF_DEFAULT = 'notice_of_default';
    case APPEAL_NOT_TIMELY = 'appeal_not_timely';
    case PENALTY = 'penalty';
    case OBJECTION_PERIOD = 'objection_period';
    case DECISION_PERIOD = 'decision_period';
    case PENALTY_PERIOD = 'penalty_period';
    case COMMITTEE_HEARING = 'committee_hearing';
    case SPECIFIED_ADJOURNMENT = 'specified_adjournment';
    case UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT = 'unspecified_adjournment_until_event';
    case UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL = 'unspecified_adjournment_until_withdrawal';
    case PENDING_TERM_AFTER_EVENT = 'pending_term_after_event';
    case PENDING_TERM_AFTER_WITHDRAWAL = 'pending_term_after_withdrawal';

    public function isSuspendable(): bool
    {
        return in_array($this, [self::FIRST, self::COMMITTEE_HEARING, self::SECOND, self::THIRD], true);
    }

    public function isDeadlineable(): bool
    {
        return in_array($this, [
            self::FIRST,
            self::SECOND,
            self::THIRD,
            self::NOTICE_OF_DEFAULT,
            self::APPEAL_NOT_TIMELY,
            self::OBJECTION_PERIOD,
            self::COMMITTEE_HEARING,
            self::SPECIFIED_ADJOURNMENT,
            self::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT,
            self::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL,
            self::PENDING_TERM_AFTER_EVENT,
            self::PENDING_TERM_AFTER_WITHDRAWAL,
        ], true);
    }
}
