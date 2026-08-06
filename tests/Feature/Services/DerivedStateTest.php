<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function collect;

class DerivedStateTest extends FeatureTestCase
{
    public function testCalendarBuildsOnPrimaryDecisionAndObjection(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
                suspensionType: SuspensionType::SUSPENSION,
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $checkDate = $calendar->findDay(CalendarDate::create('2025-02-21'));
        $this->assertNotNull($checkDate->suspensionType);
        $this->assertTrue($checkDate->applicableTerm === TermType::OBJECTION_PERIOD->value);

        $checkDate = $calendar->findDay(CalendarDate::create('2025-02-22'));
        $this->assertNull($checkDate->suspensionType);
        $this->assertTrue($checkDate->applicableTerm === TermType::OBJECTION_PERIOD->value);
    }

    public function testBezwaartermijnWordtOnderbrokenDoorOpschorting(): void
    {
        $events = collect([
            new PetitionEventData(
                type: App\Enums\PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-21'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                suspensionType: SuspensionType::SUSPENSION,
            ),
            new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-03-03'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $lastDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-02-24'));
        $this->assertTrue($lastDateOfBezwaartermijn->isLastDayOfBudget);
        $firstDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-01-14'));
        $this->assertTrue($firstDateOfBezwaartermijn->isFirstDayOfBudget);
    }

    public function testBezwaartermijnEindigtInWeekend(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-11-03'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-11-05'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
                suspensionType: SuspensionType::SUSPENSION,
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $lastDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-11-08'));
        $this->assertFalse($lastDateOfBezwaartermijn->isLastDayOfBudget);
        $this->assertTrue($lastDateOfBezwaartermijn->isATW);
        $this->assertTrue($lastDateOfBezwaartermijn->applicableTerm === TermType::OBJECTION_PERIOD->value);
        $lastDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-11-09'));
        $this->assertFalse($lastDateOfBezwaartermijn->isLastDayOfBudget);
        $this->assertFalse($lastDateOfBezwaartermijn->isFirstDayOfBudget);
        $this->assertTrue($lastDateOfBezwaartermijn->isATW);
        $this->assertTrue($lastDateOfBezwaartermijn->applicableTerm === TermType::OBJECTION_PERIOD->value);
        $lastDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-11-10'));
        $this->assertTrue($lastDateOfBezwaartermijn->isLastDayOfBudget);
        $this->assertFalse($lastDateOfBezwaartermijn->isATW);
        $firstDateOfBezwaartermijn = $calendar->findDay(CalendarDate::create('2025-11-04'));
        $this->assertTrue($firstDateOfBezwaartermijn->isFirstDayOfBudget);
    }

    public function testBeslistermijnEindigtInWeekend(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-06'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-14'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-22'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                suspensionType: SuspensionType::SUSPENSION,
            ),
            new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-01-28'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $day = $calendar->findDay(CalendarDate::create('2025-01-20'));
        $this->assertTrue($day->isLastDayOfBudget);
        $this->assertTrue($day->isDeadline);
        $this->assertFalse($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::OBJECTION_PERIOD->value);

        $day = $calendar->findDay(CalendarDate::create('2025-02-10'));
        $this->assertTrue($day->isLastDayOfBudget);
        $this->assertTrue($day->isDeadline);
        $this->assertFalse($day->isATW);
        $day = $calendar->findDay(CalendarDate::create('2025-02-08'));
        $this->assertTrue($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::DECISION_PERIOD->value);
        $day = $calendar->findDay(CalendarDate::create('2025-01-24'));
        $this->assertNotNull($day->suspensionType);
        $this->assertTrue($day->applicableTerm === TermType::DECISION_PERIOD->value);
    }

    public function testIGS(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-06'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 26, duration: 2),
                    new PenaltyData(amount: 35, duration: 2),
                    new PenaltyData(amount: 45, duration: 2),
                ],
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        // Last budget day is Saturday, so deadline shifts to Monday (ATW)
        $day = $calendar->findDay(CalendarDate::create('2025-02-08'));
        $this->assertTrue($day->isLastDayOfBudget);
        $this->assertFalse($day->isDeadline); // Deadline shifts due to ATW
        $this->assertTrue($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value);

        $day = $calendar->findDay(CalendarDate::create('2025-02-07'));
        $this->assertFalse($day->isLastDayOfBudget);
        $this->assertFalse($day->isDeadline);
        $this->assertFalse($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value);

        // ATW day (Sunday)
        $day = $calendar->findDay(CalendarDate::create('2025-02-09'));
        $this->assertFalse($day->isLastDayOfBudget);
        $this->assertFalse($day->isDeadline);
        $this->assertTrue($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value);

        // Actual deadline (after ATW shift to Monday)
        $day = $calendar->findDay(CalendarDate::create('2025-02-10'));
        $this->assertTrue($day->isLastDayOfBudget); // ATW deadline is marked as last day
        $this->assertTrue($day->isDeadline);
        $this->assertFalse($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::NOTICE_OF_DEFAULT->value);

        $day = $calendar->findDay(CalendarDate::create('2025-02-09'));
        $this->assertEquals(0, $day->penaltyTodayInEuros ?? 0);

        $day = $calendar->findDay(CalendarDate::create('2025-02-11'));
        $this->assertEquals(26, $day->penaltyTodayInEuros);

        $day = $calendar->findDay(CalendarDate::create('2025-02-12'));
        $this->assertEquals(26, $day->penaltyTodayInEuros);

        $day = $calendar->findDay(CalendarDate::create('2025-02-13'));
        $this->assertEquals(35, $day->penaltyTodayInEuros);

        $day = $calendar->findDay(CalendarDate::create('2025-02-14'));
        $this->assertEquals(35, $day->penaltyTodayInEuros);

        $day = $calendar->findDay(CalendarDate::create('2025-02-15'));
        $this->assertEquals(45, $day->penaltyTodayInEuros);

        $day = $calendar->findDay(CalendarDate::create('2025-02-16'));
        $this->assertEquals(45, $day->penaltyTodayInEuros);

        $this->assertEquals(212, $derivedState->forfeited('2025-02-17'));
    }

    public function testBNT(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 5),
                ],
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $day = $calendar->findDay(CalendarDate::create('2025-02-18'));
        $this->assertTrue($day->isFirstDayOfBudget);
        $this->assertFalse($day->isLastDayOfBudget);
        $this->assertFalse($day->isDeadline);
        $this->assertFalse($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::APPEAL_NOT_TIMELY->value);

        $day = $calendar->findDay(CalendarDate::create('2025-02-19'));
        $this->assertFalse($day->isFirstDayOfBudget);
        $this->assertTrue($day->isLastDayOfBudget);
        $this->assertTrue($day->isDeadline);
        $this->assertFalse($day->isATW);
        $this->assertTrue($day->applicableTerm === TermType::APPEAL_NOT_TIMELY->value);

        $this->assertEquals(100, $derivedState->forfeited('2025-02-20'));
        $this->assertEquals(200, $derivedState->forfeited('2025-02-21'));
        $this->assertEquals(300, $derivedState->forfeited('2025-02-22'));
        $this->assertEquals(400, $derivedState->forfeited('2025-02-23'));
        $this->assertEquals(500, $derivedState->forfeited('2025-02-24'));
        $this->assertEquals(500, $derivedState->forfeited('2025-02-25'));
    }

    public function testPenaltyPeriodEndDateForIGS(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 3),
                ],
            ),
        ]);

        $derivedState = new DerivedState();
        $derivedState->addEvents($events)->buildCalendar();

        $penaltyPeriodEndDate = $derivedState->penaltyPeriodEndDateForTerm(TermType::NOTICE_OF_DEFAULT);

        $this->assertNotNull($penaltyPeriodEndDate);
        $this->assertSame('2025-02-22', $penaltyPeriodEndDate->toDateString());
    }

    public function testPenaltyPeriodEndDateForBNT(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 4),
                ],
            ),
        ]);

        $derivedState = new DerivedState();
        $derivedState->addEvents($events)->buildCalendar();

        $penaltyPeriodEndDate = $derivedState->penaltyPeriodEndDateForTerm(TermType::APPEAL_NOT_TIMELY);

        $this->assertNotNull($penaltyPeriodEndDate);
        $this->assertSame('2025-02-23', $penaltyPeriodEndDate->toDateString());
    }

    public function testBesluit(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 5),
                ],
            ),
            new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2025-02-21'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $day = $calendar->findDay(CalendarDate::create('2025-02-22'));
        $this->assertNull($day);
    }

    public function testFullEvents(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-06'),
                createdAt: CarbonImmutable::now(),
                duration: 6,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-14'),
                createdAt: CarbonImmutable::now(),
                duration: 6,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-20'),
                createdAt: CarbonImmutable::now(),
                duration: 6,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-25'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SUSPENSION,
            ),
            new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-06'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 26, duration: 2),
                    new PenaltyData(amount: 35, duration: 2),
                    new PenaltyData(amount: 45, duration: 2),
                ],
            ),
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 5),
                ],
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-03-01'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2025-02-21'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $derivedState = new DerivedState();
        $calendar = $derivedState->addEvents($events)->buildCalendar()->getCalendar();

        $day = $calendar->findDay(CalendarDate::create('2025-02-03'));
        $this->assertTrue($day->applicableTerm === TermType::DECISION_PERIOD->value);
        $this->assertTrue($day->isDeadline);

        $day = $calendar->findDay(CalendarDate::create('2025-02-15'));
        $this->assertTrue($day->applicableTerm === TermType::PENALTY_PERIOD->value);
        $this->assertEquals(45, $day->penaltyTodayInEuros);
    }

    public function testBezwaartermijnHasDeadline(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-18'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]);

        $calendar = new DerivedState()->addEvents($events)->buildCalendar()->getCalendar();

        $day = $calendar->findDay(CalendarDate::create('2025-04-07'));
        $this->assertTrue($day->isDeadline);
        $this->assertTrue($day->isLastDayOfBudget);
        $this->assertEquals(TermType::DECISION_PERIOD->value, $day->applicableTerm);
    }

    #[Test]
    public function testCalculatesSuspensionIfOnSameDayAsObjection(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-05'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SUSPENSION,
            ),
        ]);
        $calendar = new DerivedState()->addEvents($events)->buildCalendar()->getCalendar();

        $lastDay = collect($calendar->all())
            ->filter(static fn($day) => $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->sortByDesc(static fn($day) => $day->date->toDateString())
            ->first();

        $this->assertEquals('2025-01-27', $lastDay->date->toDateString());
    }

     #[Test]
    public function testFinalDecisionWithPenaltiesStaysActiveOnLastDay(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-11-28'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-12-05'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2026-02-28'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                penalties: [
                    new PenaltyData(amount: 23, duration: 14),
                    new PenaltyData(amount: 35, duration: 14),
                    new PenaltyData(amount: 45, duration: 14),
                ],
            ),
            new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2026-03-23'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);
        $calendar = new DerivedState()->addEvents($events)->buildCalendar()->getCalendar();

        $lastDay = $calendar
            ->sortByDesc(static fn($day) => $day->date->toDateString())
            ->first();

        $this->assertEquals('2026-03-23', $lastDay->date->toDateString());
        $this->assertEquals(TermType::PENALTY_PERIOD->value, $lastDay->applicableTerm);
        $this->assertEquals(23, $lastDay->penaltyTodayInEuros);
    }

     #[Test]
    public function testDeadlineDateReturnsNullWhenFinalResultExists(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-18'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::FINAL_RESULT,
                date: CalendarDate::create('2025-04-05'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $derivedState = new DerivedState()->addEvents($events)->buildCalendar();

        $this->assertNull($derivedState->deadlineDate());
    }

     #[Test]
    public function testDeadlineDateReturnsDateWhenNoFinalResult(): void
    {
        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-02-18'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]);

        $derivedState = new DerivedState()->addEvents($events)->buildCalendar();

        $deadlineDate = $derivedState->deadlineDate();
        $this->assertNotNull($deadlineDate);
        $this->assertEquals('2025-04-07', $deadlineDate->toDateString());
    }

    public function testDeadlineDateSwitchesToPenaltyEndAfterIgsDeadlineHasPassed(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 25));

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 3),
                ],
            ),
        ]);

        $derivedState = new DerivedState()->addEvents($events)->buildCalendar();

        // IGS deadline is 2025-02-19, penalty period runs 2025-02-20 through 2025-02-22.
        $this->assertEquals('2025-02-22', $derivedState->deadlineDate()->toDateString());
    }

    public function testDeadlineDateStaysOnIgsDeadlineUntilItHasPassed(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 19));

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 3),
                ],
            ),
        ]);

        $derivedState = new DerivedState()->addEvents($events)->buildCalendar();

        $this->assertEquals('2025-02-19', $derivedState->deadlineDate()->toDateString());
    }

    public function testDeadlineDateStaysOnIgsDeadlineWhenNoPenaltyPeriodExists(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 25));

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-17'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
            ),
        ]);

        $derivedState = new DerivedState()->addEvents($events)->buildCalendar();

        $this->assertEquals('2025-02-19', $derivedState->deadlineDate()->toDateString());
    }
}
