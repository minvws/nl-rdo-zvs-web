<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum HearingForm: string
{
    case TELEPHONE = 'telephone';
    case DIGITAL = 'digital';
    case PHYSICAL = 'physical';
    case COMMITTEE = 'committee';

    public function label(): string
    {
        return __('hearing_form.' . $this->value);
    }
}
