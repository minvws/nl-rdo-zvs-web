<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\NotificationRuleTiming;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationRuleTimingTest extends TestCase
{
    /**
     * @return array<string, array{NotificationRuleTiming, int, int}>
     */
    public static function timingProvider(): array
    {
        return [
            'before' => [NotificationRuleTiming::BEFORE, 3, -3],
            'on' => [NotificationRuleTiming::ON, 0, 0],
            'after' => [NotificationRuleTiming::AFTER, 3, 3],
        ];
    }

    #[Test]
    #[DataProvider('timingProvider')]
    public function itConvertsBetweenTimingAndOffset(NotificationRuleTiming $timing, int $days, int $offsetDays): void
    {
        $this->assertSame($offsetDays, $timing->offset($days));
        $this->assertSame($timing, NotificationRuleTiming::fromOffset($offsetDays));
    }

    #[Test]
    public function aNotificationOnTheDayIgnoresTheDays(): void
    {
        $this->assertSame(0, NotificationRuleTiming::ON->offset(5));
    }
}
