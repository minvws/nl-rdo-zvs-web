<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\PeriodGenerators\UnspecifiedAdjournmentPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;

class UnspecifiedAdjournmentPeriodGeneratorTest extends TestCase
{
    #[Test]
    public function testFreezesDaysBetweenStartAndEndExclusive(): void
    {
        $calendar = new EventCalendar();
        $events = collect([
            $this->adjournment('2025-01-10'),
            $this->adjournmentEnd('2025-01-13', AdjournmentEndReason::Event),
        ]);

        (new UnspecifiedAdjournmentPeriodGenerator())->generate($events, $calendar);

        // The frozen range is [start, end): start up to the day before the end event.
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-10'))->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-11'))->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-12'))->isUnspecifiedAdjournment);

        // The end-event day itself counts towards the term again, so it is not frozen.
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-13')));
    }

    #[Test]
    public function testOpenAdjournmentFreezesUpToButNotIncludingToday(): void
    {
        $calendar = new EventCalendar();
        $start = CalendarDate::today()->addDays(-5);
        $events = collect([$this->adjournment($start->toDateString())]);

        (new UnspecifiedAdjournmentPeriodGenerator())->generate($events, $calendar);

        $this->assertTrue($calendar->findDay($start)->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::today()->addDays(-1))->isUnspecifiedAdjournment);
        $this->assertNull($calendar->findDay(CalendarDate::today()));
    }

    #[Test]
    public function testMarksWithdrawalOnEndDay(): void
    {
        $calendar = new EventCalendar();
        $events = collect([
            $this->adjournment('2025-01-10'),
            $this->adjournmentEnd('2025-01-13', AdjournmentEndReason::Withdrawal),
        ]);

        (new UnspecifiedAdjournmentPeriodGenerator())->generate($events, $calendar);

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-13'))->isUnspecifiedAdjournmentWithdrawal);
    }

    #[Test]
    public function testMultipleCyclesFreezeEachRange(): void
    {
        $calendar = new EventCalendar();
        $events = collect([
            $this->adjournment('2025-01-10'),
            $this->adjournmentEnd('2025-01-12', AdjournmentEndReason::Event),
            $this->adjournment('2025-01-20'),
            $this->adjournmentEnd('2025-01-22', AdjournmentEndReason::Event),
        ]);

        (new UnspecifiedAdjournmentPeriodGenerator())->generate($events, $calendar);

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-10'))->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-11'))->isUnspecifiedAdjournment);
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-12')));
        // Gap between the two cycles is untouched.
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-15')));
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-20'))->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-21'))->isUnspecifiedAdjournment);
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-22')));
    }

    #[Test]
    public function testClosedAdjournmentShiftsDecisionDeadlineForwardWithoutSpendingBudget(): void
    {
        $baseEvents = [
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ];

        $withoutAdjournment = $this->build(collect($baseEvents));

        // Five frozen days (03-01..03-05); the end event on 03-06 counts again.
        $withAdjournment = $this->build(collect([
            ...$baseEvents,
            $this->adjournment('2025-03-01'),
            $this->adjournmentEnd('2025-03-06', AdjournmentEndReason::Event),
        ]));

        // Frozen days do not spend budget, so the number of decision-period budget days is conserved.
        $this->assertSame(
            $this->decisionBudgetDayCount($withoutAdjournment->getCalendar()),
            $this->decisionBudgetDayCount($withAdjournment->getCalendar()),
        );

        // The five frozen days sit inside the decision period without spending budget.
        $frozenDecisionDays = $withAdjournment->getCalendar()->filter(
            static fn(EventCalendarDay $day): bool => $day->isUnspecifiedAdjournment
                && $day->applicableTerm === TermType::DECISION_PERIOD->value
                && !$day->isBudgetDay,
        );
        $this->assertCount(5, $frozenDecisionDays);

        // The decision deadline is pushed forward by the freeze.
        $this->assertTrue(
            $withAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD)->greaterThan(
                $withoutAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD),
            ),
        );
    }

    #[Test]
    public function testOpenAdjournmentMakesPetitionDeadlineToday(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            $this->adjournment('2025-03-01'),
        ]);

        $derivedState = $this->build($events);

        $this->assertTrue($derivedState->deadlineDate()->equals(CalendarDate::today()));
    }

    #[Test]
    public function testAdjournmentDuringObjectionPeriodShiftsBothTerms(): void
    {
        $baseEvents = [
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ];

        $withoutAdjournment = $this->build(collect($baseEvents));

        // Adjournment starts inside the objection period (bezwaartermijn) and lasts four days.
        $withAdjournment = $this->build(collect([
            ...$baseEvents,
            $this->adjournment('2025-01-10'),
            $this->adjournmentEnd('2025-01-14', AdjournmentEndReason::Event),
        ]));

        $this->assertTrue(
            $withAdjournment->deadlineDateForTerm(TermType::OBJECTION_PERIOD)->greaterThan(
                $withoutAdjournment->deadlineDateForTerm(TermType::OBJECTION_PERIOD),
            ),
        );
        $this->assertTrue(
            $withAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD)->greaterThan(
                $withoutAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD),
            ),
        );

        // The objection-period budget is preserved; the frozen days only shift it forward.
        $this->assertSame(
            $withoutAdjournment->getCalendar()->filter(
                static fn(EventCalendarDay $day): bool => $day->isBudgetDay
                    && $day->applicableTerm === TermType::OBJECTION_PERIOD->value,
            )->count(),
            $withAdjournment->getCalendar()->filter(
                static fn(EventCalendarDay $day): bool => $day->isBudgetDay
                    && $day->applicableTerm === TermType::OBJECTION_PERIOD->value,
            )->count(),
        );
    }

    private function build(Collection $events): DerivedState
    {
        return (new DerivedState())->addEvents($events)->buildCalendar();
    }

    private function decisionBudgetDayCount(EventCalendar $calendar): int
    {
        return $calendar->filter(
            static fn(EventCalendarDay $day): bool => $day->isBudgetDay
                && $day->applicableTerm === TermType::DECISION_PERIOD->value,
        )->count();
    }

    private function adjournment(string $date): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
        );
    }

    private function adjournmentEnd(string $date, AdjournmentEndReason $reason): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            reasoning: $reason->value,
        );
    }
}
