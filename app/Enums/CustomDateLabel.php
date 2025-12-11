<?php

declare(strict_types=1);

namespace App\Enums;

use function in_array;

enum CustomDateLabel: string
{
    case DATE_WITHDRAWN = 'date_withdrawn';
    case DATE_SETTLEMENT_WITHOUT_DECISION = 'date_settlement_without_decision';
    case DATE_RULING = 'date_ruling';
    case DATE_APPOINTMENT_WITH_APPLICANT = 'date_appointment_with_applicant';
    case DATE_DECISION_ON_APPEAL = 'date_decision_on_appeal';
    case DATE_PUBLIC_HEARING = 'date_public_hearing';
    case DATE_COURT_SESSION = 'date_court_session';
    case DATE_OF_LAST_DECISION = 'date_of_last_decision';
    case DATE_OF_FORWARDING = 'date_of_forwarding';

    public function isAffectingDateOfClose(): bool
    {
        return in_array($this, [
            self::DATE_WITHDRAWN,
            self::DATE_SETTLEMENT_WITHOUT_DECISION,
            self::DATE_RULING,
            self::DATE_DECISION_ON_APPEAL,
            self::DATE_OF_LAST_DECISION,
            self::DATE_OF_FORWARDING,
        ], true);
    }
}
