<?php

declare(strict_types=1);

namespace App\Enums;

enum ProcessingStepStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case CLOSED = 'closed';

    public static function default(): self
    {
        return self::PENDING;
    }
}
