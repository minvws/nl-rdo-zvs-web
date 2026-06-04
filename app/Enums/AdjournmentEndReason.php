<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum AdjournmentEndReason: string
{
    case Event = 'event';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return __('adjournment_end_reason.' . $this->value);
    }
}
