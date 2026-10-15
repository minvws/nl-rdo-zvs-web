<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Exports\ExcelDate;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Tests\Feature\FeatureTestCase;

class ExcelDateTest extends FeatureTestCase
{
    public function testFromCalendarDate(): void
    {
        $this->assertSame(44_197.0, ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')));
    }

    public function testFromCalendarDateWithoutDate(): void
    {
        $this->assertNull(ExcelDate::fromCalendarDate(null));
    }

    public function testFromDateTimeUsesTheDisplayTimezone(): void
    {
        $date = CarbonImmutable::create(2026, 6, 30, 22, 30, 0, 'UTC');

        $this->assertEqualsWithDelta(46_204 + 30 / 1_440, ExcelDate::fromDateTime($date), 0.000_001);
    }

    public function testFromDateTimeWithoutDate(): void
    {
        $this->assertNull(ExcelDate::fromDateTime(null));
    }
}
