<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusGroup: string
{
    case NOT_CLOSED = 'not_closed';
    case INTAKE = 'intake';
    case PENDING = 'pending';
    case FINISHED = 'finished';
    case CLOSED = 'closed';
}
