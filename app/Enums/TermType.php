<?php

declare(strict_types=1);

namespace App\Enums;

use function __;
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

    public function label(): string
    {
        return __(match ($this) {
            self::FIRST => 'term.term_type.first',
            self::SECOND => 'term.term_type.second',
            self::THIRD => 'term.term_type.appointment_with_applicant',
            self::SUSPENSION => 'term.term_type.suspension',
            self::NOTICE_OF_DEFAULT => 'term.notice_of_default.default',
            self::APPEAL_NOT_TIMELY => 'term.appeal_not_timely.default',
            self::PENALTY => 'term.term_type.penalty',
            self::OBJECTION_PERIOD => 'term.objection_period.default',
            self::DECISION_PERIOD => 'term.decision_period.default',
            self::PENALTY_PERIOD => 'term.penalty_period.default',
            self::COMMITTEE_HEARING => 'term.term_type.committee_hearing',
            self::SPECIFIED_ADJOURNMENT => 'term.term_type.specified_adjournment',
            self::UNSPECIFIED_ADJOURNMENT_UNTIL_EVENT => 'term.term_type.unspecified_adjournment_until_event',
            self::UNSPECIFIED_ADJOURNMENT_UNTIL_WITHDRAWAL => 'term.term_type.unspecified_adjournment_until_withdrawal',
            self::PENDING_TERM_AFTER_EVENT => 'term.term_type.pending_term_after_event',
            self::PENDING_TERM_AFTER_WITHDRAWAL => 'term.term_type.pending_term_after_withdrawal',
        });
    }

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
