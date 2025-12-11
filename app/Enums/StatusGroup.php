<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusGroup: string
{
    case INTAKE = 'intake';
    case PENDING = 'pending';
    case FINISHED = 'finished';
    case CLOSED = 'closed';
}
