<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How the rule form shows a rule's offset from its anchor date: a number of days before it, on it, or after it.
 */
enum NotificationRuleTiming: string
{
    case BEFORE = 'before';
    case ON = 'on';
    case AFTER = 'after';

    public static function fromOffset(int $offsetDays): self
    {
        return match (true) {
            $offsetDays < 0 => self::BEFORE,
            $offsetDays > 0 => self::AFTER,
            default => self::ON,
        };
    }

    public function offset(int $days): int
    {
        return match ($this) {
            self::BEFORE => -$days,
            self::ON => 0,
            self::AFTER => $days,
        };
    }
}
