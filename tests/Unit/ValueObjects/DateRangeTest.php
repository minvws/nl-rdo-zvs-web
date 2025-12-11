<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Tests\TestCase;
use Webmozart\Assert\InvalidArgumentException;

class DateRangeTest extends TestCase
{
    public function testDateRangeWithLowerEndDateThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new DateRange(CalendarDate::create('2025-01-07'), CalendarDate::create('2025-01-06'));
    }

    public function testDateRangeWithEqualDates(): void
    {
        $date = CalendarDate::create('2025-01-07');
        $dateRange = new DateRange($date, $date);
        $this->assertInstanceOf(DateRange::class, $dateRange);
    }
}
