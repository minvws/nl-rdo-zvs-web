<?php

declare(strict_types=1);

namespace App\Enums;

use function __;

enum DecisionType: string
{
    case CHAT = 'chat';
    case REGULAR = 'regular';

    public function label(): string
    {
        return match ($this) {
            self::CHAT => __('decision.type.chat'),
            self::REGULAR => __('decision.type.regular'),
        };
    }
}
