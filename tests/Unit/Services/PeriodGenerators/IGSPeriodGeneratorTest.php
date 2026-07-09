<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DeadlineCalculatorInterface;
use App\Services\PeriodGenerators\IGSPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;
use function sprintf;

class IGSPeriodGeneratorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('legal-term-deadline-calculator', new class () implements DeadlineCalculatorInterface {
            public function calculate(CalendarDate $proposedDeadline): CalendarDate
            {
                while ($proposedDeadline->isWeekend()) {
                    $proposedDeadline = $proposedDeadline->addDay();
                }

                return $proposedDeadline;
            }
        });
    }

    #[Test]
    public function testGeneratesNoBudgetDaysWhenNoIGSEvents(): void
    {
        $generator = new IGSPeriodGenerator();
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
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
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
    public function testGeneratesBudgetDaysWithIGSEvent(): void
    {
        $generator = new IGSPeriodGenerator();
        $startDate = '2025-01-01';
        $budget = 3;
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create($startDate),
                createdAt: CarbonImmutable::now(),
                duration: $budget,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Should generate 3 budget days starting from 2025-01-02 (date + 1 day)
        $expectedBudgetDates = [
            '2025-01-02',
            '2025-01-03',
            '2025-01-04',
        ];

        foreach ($expectedBudgetDates as $date) {
            $day = $calendar->findDay(CalendarDate::create($date));
            $this->assertNotNull($day);
            $this->assertEquals(TermType::NOTICE_OF_DEFAULT->value, $day->applicableTerm);
            $this->assertTrue($day->isBudgetDay);
        }

        // Check first and last budget day markers
        $firstDay = $calendar->findDay(CalendarDate::create('2025-01-02'));
        $weekendDay = $calendar->findDay(CalendarDate::create('2025-01-04')); // Last budget day (Saturday)

        $this->assertTrue($firstDay->isFirstDayOfBudget);
        $this->assertFalse($firstDay->isLastDayOfBudget);

        // 2025-01-04 is Saturday, so ATW is applied
        $this->assertTrue($weekendDay->isLastDayOfBudget);
        $this->assertTrue($weekendDay->isATW ?? false);
        $this->assertFalse($weekendDay->isDeadline ?? false);

        // ATW day: 2025-01-05 (Sunday)
        $atwDay = $calendar->findDay(CalendarDate::create('2025-01-05'));
        $this->assertNotNull($atwDay);
        $this->assertTrue($atwDay->isATW ?? false);
        $this->assertFalse($atwDay->isDeadline ?? false);

        // Actual deadline: 2025-01-06 (Monday)
        $deadlineDay = $calendar->findDay(CalendarDate::create('2025-01-06'));
        $this->assertNotNull($deadlineDay);
        $this->assertTrue($deadlineDay->isDeadline);
        $this->assertFalse($deadlineDay->isATW ?? false);
    }

    #[Test]
    public function testGeneratesPenaltyPeriodsAfterBudgetDays(): void
    {
        $generator = new IGSPeriodGenerator();
        $startDate = '2025-01-01';
        $budget = 2;
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create($startDate),
                createdAt: CarbonImmutable::now(),
                duration: $budget,
                penalties: [
                    new PenaltyData(amount: 150, duration: 3),
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
            $this->assertEquals(150, $day->penaltyTodayInEuros);
        }
    }

    #[Test]
    public function testGeneratesMultiplePenaltyPeriodsWithDifferentAmounts(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
                penalties: [
                    new PenaltyData(amount: 200, duration: 2),
                    new PenaltyData(amount: 75, duration: 2),
                ],
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Check first penalty period: 2025-01-03, 2025-01-04
        $this->assertEquals(200, $calendar->findDay(CalendarDate::create('2025-01-03'))->penaltyTodayInEuros);
        $this->assertEquals(200, $calendar->findDay(CalendarDate::create('2025-01-04'))->penaltyTodayInEuros);

        // Check second penalty period: 2025-01-05, 2025-01-06
        $this->assertEquals(75, $calendar->findDay(CalendarDate::create('2025-01-05'))->penaltyTodayInEuros);
        $this->assertEquals(75, $calendar->findDay(CalendarDate::create('2025-01-06'))->penaltyTodayInEuros);
    }

    #[Test]
    public function testHandlesMultipleIGSEvents(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
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
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
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

        new PenaltyData(amount: 150, duration: 0);
    }

    #[Test]
    public function testWithdrawnIGSGeneratesNoCalendarDays(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::parse('2025-01-01'),
                duration: 3,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::parse('2025-01-05'),
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    #[Test]
    public function testSecondIGSAfterWithdrawalGeneratesCalendarDays(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::parse('2025-01-01'),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::parse('2025-01-05'),
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::parse('2025-02-01'),
                duration: 2,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-02')));
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-03')));

        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-02-02')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-02-03')));
    }

    #[Test]
    public function testAllWithdrawnIGSEventsGenerateNoCalendarDays(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::parse('2025-01-01'),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::parse('2025-01-05'),
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::parse('2025-02-01'),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-02-10'),
                createdAt: CarbonImmutable::parse('2025-02-10'),
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertEmpty($calendar->all());
    }

    #[Test]
    public function testOnlyLastActiveIGSGeneratesCalendarDaysWhenMultiplePairs(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::parse('2025-01-01'),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::parse('2025-01-05'),
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::parse('2025-02-01'),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
                date: CalendarDate::create('2025-02-10'),
                createdAt: CarbonImmutable::parse('2025-02-10'),
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-03-01'),
                createdAt: CarbonImmutable::parse('2025-03-01'),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-02')));
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-02-02')));

        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-03-02')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-03-03')));
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-03-04')));
    }

     #[Test]
    public function testMarksCorrectFirstAndLastDaysInBudget(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 4,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        // Budget days: 2025-01-02 (Thu), 2025-01-03 (Fri), 2025-01-04 (Sat), 2025-01-05 (Sun)
        // 2025-01-05 (Sun) is weekend, so ATW shifts deadline to 2025-01-06 (Mon)
        for ($i = 1; $i <= 4; $i++) {
            $date = sprintf('2025-01-%02d', $i + 1);
            $day = $calendar->findDay(CalendarDate::create($date));

            if ($i === 1) {
                // 2025-01-02 (Thursday) - first budget day
                $this->assertTrue($day->isFirstDayOfBudget);
                $this->assertFalse($day->isLastDayOfBudget);
            } elseif ($i === 4) {
                // 2025-01-05 (Sunday) - last budget day with ATW
                $this->assertFalse($day->isFirstDayOfBudget);
                $this->assertTrue($day->isLastDayOfBudget);
                $this->assertTrue($day->isATW ?? false);
                $this->assertFalse($day->isDeadline ?? false);
            } else {
                // 2025-01-03 (Friday), 2025-01-04 (Saturday) - middle days
                $this->assertFalse($day->isFirstDayOfBudget);
                $this->assertFalse($day->isLastDayOfBudget);
            }
        }

        // Verify actual deadline: 2025-01-06 (Monday)
        $deadlineDay = $calendar->findDay(CalendarDate::create('2025-01-06'));
        $this->assertNotNull($deadlineDay);
        $this->assertTrue($deadlineDay->isDeadline);
        $this->assertFalse($deadlineDay->isATW ?? false);
        $this->assertTrue($deadlineDay->isLastDayOfBudget ?? false);
    }

     #[Test]
    public function testIGSWithinBeslistermijnGeneratesNoTermPeriod(): void
    {
        $generator = new IGSPeriodGenerator();

        // Build calendar with decision period: 2025-01-02 to 2025-01-06 (5 days)
        // Deadline on 2025-01-06
        $calendar = new EventCalendar();
        for ($i = 1; $i <= 5; $i++) {
            $date = CalendarDate::create(sprintf('2025-01-%02d', $i + 1));
            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => true,
                'isFirstDayOfBudget' => $i === 1,
                'isLastDayOfBudget' => $i === 5,
                'isDeadline' => $i === 5,
            ]);
        }

        // IGS received on 2025-01-04 → within decision period (< deadline 2025-01-06)
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-04'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
        ]);

        $generator->generate($events, $calendar);

        // Only decision period days should remain in calendar, no IGS budget days
        $igsDay = $calendar->findDay(CalendarDate::create('2025-01-05'));
        $this->assertEquals(TermType::DECISION_PERIOD->value, $igsDay?->applicableTerm);

        // No NOTICE_OF_DEFAULT days present
        $nodDays = $calendar->filter(
            fn($day) => $day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value,
        );
        $this->assertEmpty($nodDays);
    }

     #[Test]
    public function testIGSOnDeadlineDateGeneratesNoTermPeriod(): void
    {
        // On exact deadline date → also no period (≤ deadline)
        $generator = new IGSPeriodGenerator();

        $calendar = new EventCalendar();
        $calendar->upsertDay(CalendarDate::create('2025-01-06'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isBudgetDay' => true,
            'isFirstDayOfBudget' => true,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-06'), // on the deadline itself
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
        ]);

        $generator->generate($events, $calendar);

        $nodDays = $calendar->filter(
            fn($day) => $day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value,
        );
        $this->assertEmpty($nodDays);
    }

      #[Test]
    public function testIGSAfterBeslistermijnDeadlineGeneratesTermPeriod(): void
    {
        // IGS after deadline → normal operation
        $generator = new IGSPeriodGenerator();

        $calendar = new EventCalendar();
        $calendar->upsertDay(CalendarDate::create('2025-01-06'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isBudgetDay' => true,
            'isFirstDayOfBudget' => true,
            'isLastDayOfBudget' => true,
            'isDeadline' => true,
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-07'), // day after deadline
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
        ]);

        $generator->generate($events, $calendar);

        // Budget days start on 2025-01-08 (event->date + 1)
        $this->assertNotNull($calendar->findDay(CalendarDate::create('2025-01-08')));
        $this->assertEquals(
            TermType::NOTICE_OF_DEFAULT->value,
            $calendar->findDay(CalendarDate::create('2025-01-08'))->applicableTerm,
        );
    }

      #[Test]
    public function testATWIsAppliedWhenLastBudgetDayFallsOnWeekend(): void
    {
        // Setup:
        // - Event date: 2025-01-04 (Zaterdag)
        // - Duration: 7 days
        // - Start date: 2025-01-05 (Zondag)
        // - Last budget day: 2025-01-11 (Zaterdag) ← valt op weekend
        // - ATW days: 2025-01-12 (Zondag)
        // - Actual deadline: 2025-01-13 (Maandag)

        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-04'), // Zaterdag
                createdAt: CarbonImmutable::now(),
                duration: 7,
            ),
        ]);

        $calendar = new EventCalendar();
        $generator->generate($events, $calendar);

        // Last budget day 2025-01-11 (Zaterdag) moet ATW zijn, NIET isDeadline
        $weekendDay = $calendar->findDay(CalendarDate::create('2025-01-11'));
        $this->assertNotNull($weekendDay, 'Saturday (2025-01-11) should exist in calendar');
        $this->assertTrue($weekendDay->isATW ?? false, 'Saturday should be marked as ATW');
        $this->assertFalse($weekendDay->isDeadline ?? false, 'Saturday should NOT be deadline');
        $this->assertTrue($weekendDay->isLastDayOfBudget ?? false, 'Saturday should be marked as last budget day');

        // ATW-dag 2025-01-12 (Zondag) moet ATW zijn
        $sundayDay = $calendar->findDay(CalendarDate::create('2025-01-12'));
        $this->assertNotNull($sundayDay, 'Sunday (2025-01-12) should exist as ATW day');
        $this->assertEquals(TermType::NOTICE_OF_DEFAULT->value, $sundayDay->applicableTerm);
        $this->assertTrue($sundayDay->isATW ?? false, 'Sunday should be marked as ATW');
        $this->assertFalse($sundayDay->isDeadline ?? false, 'Sunday should NOT be deadline');

        // Deadline op 2025-01-13 (Maandag)
        $mondayDay = $calendar->findDay(CalendarDate::create('2025-01-13'));
        $this->assertNotNull($mondayDay, 'Monday (2025-01-13) should exist as deadline');
        $this->assertTrue($mondayDay->isDeadline ?? false, 'Monday should be deadline');
        $this->assertFalse($mondayDay->isATW ?? false, 'Monday should NOT be ATW');
        $this->assertTrue($mondayDay->isLastDayOfBudget ?? false, 'Monday should be final deadline');
    }

      #[Test]
    public function testNoATWWhenLastBudgetDayIsWeekday(): void
    {
        // Setup:
        // - Event date: 2025-01-01 (Woensdag)
        // - Duration: 2 days
        // - Start date: 2025-01-02 (Donderdag)
        // - Last budget day: 2025-01-03 (Vrijdag) ← valt op weekdag
        // - Geen ATW nodig

        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'), // Woensdag
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
        ]);

        $calendar = new EventCalendar();
        $generator->generate($events, $calendar);

        // Last budget day 2025-01-03 (Vrijdag) IS de deadline, geen ATW
        $fridayDay = $calendar->findDay(CalendarDate::create('2025-01-03'));
        $this->assertNotNull($fridayDay, 'Friday (2025-01-03) should exist');
        $this->assertTrue($fridayDay->isDeadline ?? false, 'Friday should be deadline');
        $this->assertFalse($fridayDay->isATW ?? false, 'Friday should NOT be ATW');
        $this->assertTrue($fridayDay->isLastDayOfBudget ?? false, 'Friday should be last budget day');

        // Geen dagen na 2025-01-03
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-04')), 'No ATW days should follow Friday');
    }

    #[Test]
    public function testExactlyOneDeadlineMarkedAfterAddIGSBudgetDays(): void
    {
        $generator = new IGSPeriodGenerator();
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
        ]);
        $calendar = new EventCalendar();

        $generator->generate($events, $calendar);

        $deadlineDays = $calendar->filter(
            static fn($day): bool => $day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value
                && $day->isDeadline,
        );

        $this->assertCount(1, $deadlineDays, 'Exactly one IGS deadline must be marked after generate()');
    }
}
