<?php

declare(strict_types=1);

namespace App\Enums;

enum Occurrence: string
{
    case PETITION_TYPE = 'occurrence.petition';
    case DECISION_TYPE = 'occurrence.decision';
    case ATTACH_ACTION = 'occurrence.action_linked';
    case DETACH_ACTION = 'occurrence.action_unlinked';
}
