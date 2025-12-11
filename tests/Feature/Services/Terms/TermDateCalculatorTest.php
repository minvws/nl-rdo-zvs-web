<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Terms;

use App\Services\Terms\TermDateCalculator;
use App\ValueObjects\CalendarDate;
use Tests\Feature\FeatureTestCase;

class TermDateCalculatorTest extends FeatureTestCase
{
    public function testCalculateEndDateFromStartDateAndDuration(): void
    {
        $startDate = CalendarDate::create('2025-01-01');
        $durationInDays = 14;

        $endDate = TermDateCalculator::calculateEndDate($startDate, $durationInDays);

        $this->assertEquals('2025-01-14', $endDate->format('Y-m-d'));
    }

    public function testCalculateEndDateWithSingleDay(): void
    {
        $startDate = CalendarDate::create('2025-01-01');
        $durationInDays = 1;

        $endDate = TermDateCalculator::calculateEndDate($startDate, $durationInDays);

        $this->assertEquals('2025-01-01', $endDate->format('Y-m-d'));
    }

    public function testCalculateEndDateWithLongDuration(): void
    {
        $startDate = CalendarDate::create('2025-01-01');
        $durationInDays = 365;

        $endDate = TermDateCalculator::calculateEndDate($startDate, $durationInDays);

        $this->assertEquals('2025-12-31', $endDate->format('Y-m-d'));
    }

    public function testCalculateDurationFromStartAndEndDate(): void
    {
        $startDate = CalendarDate::create('2025-01-01');
        $endDate = CalendarDate::create('2025-01-14');

        $duration = TermDateCalculator::calculateDuration($startDate, $endDate);

        $this->assertEquals(14, $duration);
    }

    public function testCalculateDurationWithSameStartAndEndDate(): void
    {
        $startDate = CalendarDate::create('2025-01-01');
        $endDate = CalendarDate::create('2025-01-01');

        $duration = TermDateCalculator::calculateDuration($startDate, $endDate);

        $this->assertEquals(1, $duration);
    }

    public function testCalculateDurationAcrossMonths(): void
    {
        $startDate = CalendarDate::create('2025-01-15');
        $endDate = CalendarDate::create('2025-02-28');

        $duration = TermDateCalculator::calculateDuration($startDate, $endDate);

        $this->assertEquals(45, $duration);
    }

    public function testCalculateDurationAcrossYears(): void
    {
        $startDate = CalendarDate::create('2024-12-01');
        $endDate = CalendarDate::create('2025-01-31');

        $duration = TermDateCalculator::calculateDuration($startDate, $endDate);

        $this->assertEquals(62, $duration);
    }

    public function testCalculationsAreSymmetric(): void
    {
        $startDate = CalendarDate::create('2025-04-01');
        $originalDuration = 28;

        $calculatedEndDate = TermDateCalculator::calculateEndDate($startDate, $originalDuration);
        $calculatedDuration = TermDateCalculator::calculateDuration($startDate, $calculatedEndDate);

        $this->assertEquals($originalDuration, $calculatedDuration);
    }

    public function testCalculationsWithLeapYear(): void
    {
        $startDate = CalendarDate::create('2024-02-01');
        $endDate = CalendarDate::create('2024-02-29');

        $duration = TermDateCalculator::calculateDuration($startDate, $endDate);
        $this->assertEquals(29, $duration);

        $calculatedEndDate = TermDateCalculator::calculateEndDate($startDate, $duration);
        $this->assertEquals($endDate->format('Y-m-d'), $calculatedEndDate->format('Y-m-d'));
    }
}
