<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Services\DerivedState;
use App\Services\PeriodGenerators\UnspecifiedAdjournmentPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;

class UnspecifiedAdjournmentPeriodGeneratorTest extends TestCase
{
    #[Test]
    public function testMarksDecisionPeriodDaysAfterAdjournment(): void
    {
        $calendar = new EventCalendar();
        $adjournmentDate = CalendarDate::create('2025-01-10');
        $decisionDay1 = CalendarDate::create('2025-01-11');
        $decisionDay2 = CalendarDate::create('2025-01-12');
        $otherDay = CalendarDate::create('2025-01-09');

        // Voeg dagen toe aan de kalender
        $calendar->upsertDay($adjournmentDate, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
        ]);
        $calendar->upsertDay($decisionDay1, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isDeadline' => true,
        ]);
        $calendar->upsertDay($decisionDay2, [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            'isDeadline' => true,
        ]);
        $calendar->upsertDay($otherDay, [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        $day1 = $calendar->findDay($decisionDay1);
        $day2 = $calendar->findDay($decisionDay2);
        $other = $calendar->findDay($otherDay);

        $this->assertTrue($day1->isUnspecifiedAdjournment);
        $this->assertTrue($day2->isUnspecifiedAdjournment);
        $this->assertFalse($other->isUnspecifiedAdjournment);
        $this->assertFalse($day1->isDeadline);
        $this->assertFalse($day2->isDeadline);
    }

    #[Test]
    public function testMarksPeriodBetweenStartAndEndWhenEndEventExists(): void
    {
        $calendar = new EventCalendar();
        $startDate = CalendarDate::create('2025-01-10');
        $day1 = CalendarDate::create('2025-01-11');
        $day2 = CalendarDate::create('2025-01-12');
        $endDate = CalendarDate::create('2025-01-13');
        $dayAfterEnd = CalendarDate::create('2025-01-14');

        // Voeg decision_period dagen toe
        foreach ([$startDate, $day1, $day2, $endDate, $dayAfterEnd] as $date) {
            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isDeadline' => true,
            ]);
        }

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        // Dagen tussen START en END moeten gemarkeerd zijn
        $this->assertTrue($calendar->findDay($startDate)->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay($day1)->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay($day2)->isUnspecifiedAdjournment);

        // END dag en daarna NIET gemarkeerd als adjournment
        $this->assertFalse($calendar->findDay($endDate)->isUnspecifiedAdjournment);
        $this->assertFalse($calendar->findDay($dayAfterEnd)->isUnspecifiedAdjournment);

        // Deadline op oorspronkelijke days moet verwijderd zijn
        $this->assertFalse($calendar->findDay($startDate)->isDeadline);
        $this->assertFalse($calendar->findDay($day1)->isDeadline);
        $this->assertFalse($calendar->findDay($day2)->isDeadline);
        $this->assertFalse($calendar->findDay($endDate)->isDeadline);
    }

    #[Test]
    public function testGeneratesAdjournmentBudgetDaysAfterEnd(): void
    {
        $calendar = new EventCalendar();
        $startDate = CalendarDate::create('2025-01-10');

        // Voeg decision_period dagen toe tussen START en END
        foreach ([$startDate, CalendarDate::create('2025-01-11'), CalendarDate::create('2025-01-12')] as $date) {
            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
            ]);
        }

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        // Controleer dat er 5 aanhoudingbudget dagen zijn gegenereerd vanaf END + 1
        $budgetDays = collect($calendar->all())
            ->filter(static fn($day) => $day->date->greaterThanOrEqualTo(CalendarDate::create('2025-01-14')))
            ->filter(static fn($day) => $day->applicableTerm === TermType::DECISION_PERIOD->value && $day->isBudgetDay)
            ->count();

        $this->assertEquals(5, $budgetDays);

        // Controleer first/last markers
        $firstBudgetDay = $calendar->findDay(CalendarDate::create('2025-01-14'));
        $this->assertTrue($firstBudgetDay->isFirstDayOfBudget);

        $lastBudgetDay = $calendar->findDay(CalendarDate::create('2025-01-18'));
        $this->assertTrue($lastBudgetDay->isLastDayOfBudget);
        $this->assertTrue($lastBudgetDay->isDeadline);
        // Intermediate budget days geen deadline
        $middleBudgetDay = $calendar->findDay(CalendarDate::create('2025-01-16'));
        $this->assertFalse($middleBudgetDay->isDeadline);
    }

    #[Test]
    public function testDoesNotGenerateBudgetDaysWhenDurationIsZero(): void
    {
        $calendar = new EventCalendar();

        $calendar->upsertDay(CalendarDate::create('2025-01-10'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 0,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        // Geen budget dagen na END
        $budgetDays = collect($calendar->all())
            ->filter(static fn($day) => $day->date->greaterThanOrEqualTo(CalendarDate::create('2025-01-14')))
            ->filter(static fn($day) => $day->isBudgetDay)
            ->count();

        $this->assertEquals(0, $budgetDays);
    }

    #[Test]
    public function testGeneratesAdjournmentBudgetDaysAfterEndWithinTerm(): void
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
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-21'),
                createdAt: CarbonImmutable::now(),
                duration: 40,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-03-04'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-03-08'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-05-13'),
                createdAt: CarbonImmutable::now(),
                duration: 7,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-05-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $calendar = new DerivedState()->addEvents($events)->buildCalendar()->getCalendar()->sortBy('date');

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-05-30'))->isDeadline);
    }

    #[Test]
    public function testGeneratesAdjournmentBudgetDaysAfterEndOutsideTerm(): void
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
                date: CalendarDate::create('2025-02-20'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-21'),
                createdAt: CarbonImmutable::now(),
                duration: 40,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-03-04'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::SUSPENSION_END,
                date: CalendarDate::create('2025-03-08'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-05-13'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-06-02'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $calendar = new DerivedState()->addEvents($events)->buildCalendar()->getCalendar()->sortBy('date');

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-06-07'))->isDeadline);
    }

    #[Test]
    public function testDoesNotSetDeadlineWhenNoDecisionPeriodDaysExist(): void
    {
        $calendar = new EventCalendar();

        // Add only non-decision period days
        $calendar->upsertDay(CalendarDate::create('2025-01-10'), [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);
        $calendar->upsertDay(CalendarDate::create('2025-01-11'), [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 0,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2025-01-13'),
                createdAt: CarbonImmutable::now(),
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        // Verify no deadline was set (since no decision period days exist)
        foreach ($calendar->all() as $day) {
            $this->assertFalse($day->isDeadline);
        }
    }

    #[Test]
    public function testMarksWithdrawalOnEndDayWhenReasonIsWithdrawal(): void
    {
        $calendar = new EventCalendar();
        $startDate = CalendarDate::create('2025-01-10');
        $endDate = CalendarDate::create('2025-01-13');

        foreach ([$startDate, CalendarDate::create('2025-01-11'), CalendarDate::create('2025-01-12'), $endDate] as $date) {
            $calendar->upsertDay($date, [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
            ]);
        }

        $events = collect([
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: $startDate,
                createdAt: CarbonImmutable::now(),
                duration: 3,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: $endDate,
                createdAt: CarbonImmutable::now(),
                adjournmentEndReason: AdjournmentEndReason::Withdrawal,
            ),
        ]);

        $generator = new UnspecifiedAdjournmentPeriodGenerator();
        $generator->generate($events, $calendar);

        $this->assertTrue($calendar->findDay($endDate)->isUnspecifiedAdjournmentWithdrawal);
    }
}
