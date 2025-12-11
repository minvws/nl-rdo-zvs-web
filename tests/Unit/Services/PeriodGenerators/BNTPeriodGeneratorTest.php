<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\BNTPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;

class BNTPeriodGeneratorTest extends TestCase
{
    #[Test]
    public function testGeneratesNoBudgetDaysWhenNoAppealDecisionEvents(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    #[Test]
    public function testGeneratesNoBudgetDaysWhenBudgetIsZero(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 0,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    #[Test]
    public function testGeneratesBudgetDaysWithAppealDecisionEvent(): void
    {
        $generator = new BNTPeriodGenerator();
        $startDate = '2025-01-01';
        $budget = 3;
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create($startDate),
                createdAt: CarbonImmutable::now(),
                duration: $budget,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate 3 days starting from 2025-01-02 (date + 1 day)
        $expectedDates = [
            '2025-01-02',
            '2025-01-03',
            '2025-01-04',
        ];

        foreach ($expectedDates as $date) {
            $day = $calendar->findDay(CalendarDate::create($date));
            $this->assertNotNull($day);
            $this->assertEquals(TermType::APPEAL_NOT_TIMELY->value, $day->applicableTerm);
            $this->assertTrue($day->isBudgetDay);
        }

        // Check first and last day markers
        $firstDay = $calendar->findDay(CalendarDate::create('2025-01-02'));
        $lastDay = $calendar->findDay(CalendarDate::create('2025-01-04'));

        $this->assertTrue($firstDay->isFirstDayOfBudget);
        $this->assertFalse($firstDay->isLastDayOfBudget);
        $this->assertTrue($lastDay->isLastDayOfBudget);
        $this->assertTrue($lastDay->isDeadline);
    }

    #[Test]
    public function testGeneratesPenaltyPeriodsAfterBudgetDays(): void
    {
        $generator = new BNTPeriodGenerator();
        $startDate = '2025-01-01';
        $budget = 2;
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create($startDate),
                createdAt: CarbonImmutable::now(),
                duration: $budget,
                penalties: [
                    new PenaltyData(amount: 100, duration: 3),
                ],
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Budget days: 2025-01-02, 2025-01-03
        // Penalty days should start at 2025-01-04
        $penaltyDates = ['2025-01-04', '2025-01-05', '2025-01-06'];

        foreach ($penaltyDates as $date) {
            $day = $calendar->findDay(CalendarDate::create($date));
            $this->assertNotNull($day);
            $this->assertEquals(TermType::PENALTY_PERIOD->value, $day->applicableTerm);
            $this->assertFalse($day->isBudgetDay);
            $this->assertEquals(100, $day->penaltyTodayInEuros);
        }
    }

    #[Test]
    public function testGeneratesMultiplePenaltyPeriodsWithDifferentAmounts(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
                penalties: [
                    new PenaltyData(amount: 100, duration: 2),
                    new PenaltyData(amount: 50, duration: 2),
                ],
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Check first penalty period: 2025-01-03, 2025-01-04
        $this->assertEquals(100, $calendar->findDay(CalendarDate::create('2025-01-03'))->penaltyTodayInEuros);
        $this->assertEquals(100, $calendar->findDay(CalendarDate::create('2025-01-04'))->penaltyTodayInEuros);

        // Check second penalty period: 2025-01-05, 2025-01-06
        $this->assertEquals(50, $calendar->findDay(CalendarDate::create('2025-01-05'))->penaltyTodayInEuros);
        $this->assertEquals(50, $calendar->findDay(CalendarDate::create('2025-01-06'))->penaltyTodayInEuros);
    }

    #[Test]
    public function testHandlesMultipleAppealDecisionEvents(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // First event budget: 2025-01-02, 2025-01-03
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-01-02')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-01-03')));

        // Second event budget: 2025-02-02, 2025-02-03
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-02-02')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-02-03')));
    }

    #[Test]
    public function testIgnoresEmptyPenaltyArray(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [],
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should only have the 2 budget days
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-01-02')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-01-03')));
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-04')));
    }

    #[Test]
    public function testPenaltyDataRejectsZeroDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty duration must be positive');

        new PenaltyData(amount: 100, duration: 0);
    }

    #[Test]
    public function testMarksCorrectFirstAndLastDaysInBudget(): void
    {
        $generator = new BNTPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        for ($i = 1; $i <= 5; $i++) {
            $date = '2025-01-0' . ($i + 1);
            $day = $calendar->findDay(CalendarDate::create($date));

            if ($i === 1) {
                $this->assertTrue($day->isFirstDayOfBudget);
                $this->assertFalse($day->isLastDayOfBudget);
            } elseif ($i === 5) {
                $this->assertFalse($day->isFirstDayOfBudget);
                $this->assertTrue($day->isLastDayOfBudget);
            } else {
                $this->assertFalse($day->isFirstDayOfBudget);
                $this->assertFalse($day->isLastDayOfBudget);
            }
        }
    }
}
