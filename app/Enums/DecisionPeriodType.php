<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Which part of the decision period ("beslistermijn") a calendar day belongs to. The statutory
 * term comes from the receipt; every other part extends or replaces it.
 */
enum DecisionPeriodType: string
{
    case STATUTORY = 'statutory';
    case ADJOURNMENT = 'adjournment';
    case MEETING_SCHEDULED = 'meeting_scheduled';
    case COMMITTEE_HEARING = 'committee_hearing';
    case EVENT = 'event';
    case WITHDRAWAL = 'withdrawal';
    case EXTENDED = 'extended';
}
