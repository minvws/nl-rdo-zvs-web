<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum HearingForm: string
{
    case TELEPHONE = 'telephone';
    case DIGITAL = 'digital';
    case PHYSICAL = 'physical';

    public function label(): string
    {
        return match ($this) {
            self::TELEPHONE => __('hearing_form.telephone'),
            self::DIGITAL => __('hearing_form.digital'),
            self::PHYSICAL => __('hearing_form.physical'),
        };
    }
}
