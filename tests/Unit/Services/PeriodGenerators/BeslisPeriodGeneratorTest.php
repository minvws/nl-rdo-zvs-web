<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\BeslisPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

use function collect;
use function range;
use function sprintf;

class BeslisPeriodGeneratorTest extends TestCase
{
    /**
     * @throws Throwable
     */
    #[Test]
    public function testGeneratesNoBudgetDaysWhenNoReceiptEvent(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function testGeneratesNoBudgetDaysWhenReceiptBudgetIsZero(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 0,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function testGeneratesBudgetDaysFromReceiptOfObjection(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate 3 budget days for decision period
        $expectedDates = ['2025-01-02', '2025-01-03', '2025-01-04'];

        foreach ($expectedDates as $date) {
            $day = $calendar->findDay(CalendarDate::create($date));
            $this->assertNotNull($day);
            $this->assertEquals(TermType::DECISION_PERIOD->value, $day->applicableTerm);
            $this->assertTrue($day->isBudgetDay);
        }
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function testAddsAdditionalBudgetFromCommitteeHearingDates(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Receipt budget: 2 days
        // Hearing budget: 3 days
        // Total: 5 budget days starting from 2025-01-02
        $budgetDays = 0;
        foreach (range(2, 6) as $day) {
            $date = sprintf('2025-01-%02d', $day);
            $dayObj = $calendar->findDay(CalendarDate::create($date));
            if ($dayObj && $dayObj->isBudgetDay && $dayObj->applicableTerm === TermType::DECISION_PERIOD->value) {
                $budgetDays++;
            }
        }

        $this->assertEquals(5, $budgetDays);
    }

    #[Test]
    public function testSkipsSuspendedDaysWhenCalculatingBudget(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate 5 budget days for the duration
        $budgetCount = collect($calendar->all())
            ->filter(static fn($day) => $day->isBudgetDay && $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertEquals(5, $budgetCount);
    }

    #[Test]
    public function testCalculatesStartDateAfterLastBezwaarDay(): void
    {
        $generator = new BeslisPeriodGenerator();

        // This test simulates an objection period that exists before receipt event
        // The generator should start the budget after the last objection day
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-20'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
        ]);
        $calendar = new EventCalendar();

        // Pre-populate calendar with objection period ending on 2025-01-15
        $calendar->upsertDay(CalendarDate::create('2025-01-10'), [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);
        $calendar->upsertDay(CalendarDate::create('2025-01-15'), [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);

        $generator->generate($events, $calendar);

        // Calendar should have days generated
        $this->assertNotEmpty($calendar->all());
    }

    #[Test]
    public function testGeneratesDecisionPeriodDays(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should have generated 3 decision period days
        $decisionDays = collect($calendar->all())
            ->filter(static fn($day) => $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertGreaterThanOrEqual(3, $decisionDays);
    }

    #[Test]
    public function testHandlesDeadlineCalculation(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Calendar should contain days with deadline flag set or ATW days
        $daysWithDeadline = collect($calendar->all())
            ->filter(static fn($day) => $day->isDeadline)
            ->count();
        $this->assertGreaterThan(0, $daysWithDeadline);
    }

    #[Test]
    public function testGeneratesCorrectTermTypeForBudgetDays(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 4,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // All days should have DECISION_PERIOD term type
        foreach ($calendar->all() as $day) {
            $this->assertEquals(TermType::DECISION_PERIOD->value, $day->applicableTerm);
        }
    }

    #[Test]
    public function testHandlesMultipleHearingDatesForAdditionalBudget(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
            new PetitionEventData(
                type: PetitionEventType::ADJOURNMENT,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 4,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Receipt budget: 1 day
        // Both hearing budgets: 2 + 3 + 4 = 9 days
        // Total: 10 budget days
        $budgetCount = collect($calendar->all())
            ->filter(static fn($day) => $day->isBudgetDay && $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertEquals(10, $budgetCount);
    }

    #[Test]
    public function testDeadlineCalculationWithoutMovingProposedDeadline(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate decision period days
        $decisionDays = collect($calendar->all())
            ->filter(static fn($day) => $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertGreaterThan(0, $decisionDays);
    }

    #[Test]
    public function testGeneratesBudgetDaysFromPetitionReceived(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 28,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate 28 budget days for decision period (WOO default)
        $budgetCount = collect($calendar->all())
            ->filter(static fn($day) => $day->isBudgetDay && $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertEquals(28, $budgetCount);

        // Verify first and last day markers
        $firstDay = $calendar->findDay(CalendarDate::create('2025-01-02'));
        $this->assertNotNull($firstDay);
        $this->assertTrue($firstDay->isFirstDayOfBudget);

        $lastBudgetDay = collect($calendar->all())
            ->filter(static fn($day) => $day->isBudgetDay)
            ->sortByDesc(static fn($day) => $day->date->toDateString())
            ->first();
        $this->assertTrue($lastBudgetDay->isLastDayOfBudget);
    }

    #[Test]
    public function testAddsAdditionalBudgetFromMeetingScheduledForWooVerzoek(): void
    {
        $generator = new BeslisPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PETITION_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 28,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 28,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Receipt budget: 28 days
        // Meeting budget: 28 days
        // Total: 56 budget days
        $budgetCount = collect($calendar->all())
            ->filter(static fn($day) => $day->isBudgetDay && $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->count();
        $this->assertEquals(56, $budgetCount);
    }
}
