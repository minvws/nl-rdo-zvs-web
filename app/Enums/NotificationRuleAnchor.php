<?php

declare(strict_types=1);

namespace App\Enums;

enum NotificationRuleAnchor: string
{
    case EVENT_DATE = 'event_date';
    case EVENT_END_DATE = 'event_end_date';
    case PETITION_DEADLINE = 'petition_deadline';
}
