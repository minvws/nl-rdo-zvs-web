<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Facades\LegalTermDeadlineCalculator;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function collect;

class ContinuingUnspecifiedAdjournmentPeriodGeneratorTest extends TestCase
{
    #[Test]
    public function testTermsKeepRunningSoTheBudgetIsNotShiftedForward(): void
    {
        $withoutAdjournment = $this->build($this->baseEvents());

        // A short registered term keeps the original decision deadline, because the original term
        // has not expired yet and is the longer of the two.
        $withAdjournment = $this->build(collect([
            ...$this->baseEvents()->all(),
            $this->adjournment('2025-03-01', 5, 5),
            $this->adjournmentEnd('2025-03-06', AdjournmentEndReason::Event),
        ]));

        $this->assertTrue(
            $withAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                $withoutAdjournment->deadlineDateForTerm(TermType::DECISION_PERIOD),
            ),
        );
    }

    #[Test]
    public function testOpenAdjournmentLeavesTheDecisionDeadlineEmpty(): void
    {
        $derivedState = $this->build(collect([
            ...$this->baseEvents()->all(),
            $this->adjournment('2025-03-01', 42, 42),
        ]));

        $this->assertNull($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD));
        $this->assertNull($derivedState->deadlineDate());
    }

    #[Test]
    public function testEndDayIsTheFirstDayOfTheNewDecisionTerm(): void
    {
        // The original term ran out on 2025-03-05, so only the registered term is left.
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-01-10', 10),
            $this->adjournment('2025-03-01', 10, 20),
            $this->adjournmentEnd('2025-06-02', AdjournmentEndReason::Event),
        ]));

        $firstDay = $derivedState->getCalendar()->findDay(CalendarDate::create('2025-06-02'));

        $this->assertTrue($firstDay->isFirstDayOfBudget);
        $this->assertSame(TermType::DECISION_PERIOD->value, $firstDay->applicableTerm);

        // Ten days starting on the end day means the last day is 2025-06-11.
        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate(CalendarDate::create('2025-06-11')),
            ),
        );
    }

    #[Test]
    public function testWithdrawalUsesTheWithdrawalTermAndIsMarkedOnTheEndDay(): void
    {
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-01-10', 10),
            $this->adjournment('2025-03-01', 10, 20),
            $this->adjournmentEnd('2025-06-02', AdjournmentEndReason::Withdrawal),
        ]));

        $this->assertTrue(
            $derivedState->getCalendar()
                ->findDay(CalendarDate::create('2025-06-02'))
                ->isUnspecifiedAdjournmentWithdrawal,
        );

        // Twenty days starting on the end day means the last day is 2025-06-21.
        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate(CalendarDate::create('2025-06-21')),
            ),
        );
    }

    #[Test]
    public function testUnexpiredOriginalTermWinsWhenItHasMoreDaysLeft(): void
    {
        $original = $this->originalDecisionDeadline();

        // The adjournment ends well before the original deadline, so the days it has left beat
        // the registered term of 5 and the deadline does not move.
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', 5, 5),
            $this->adjournmentEnd('2025-02-10', AdjournmentEndReason::Event),
        ]));

        $this->assertTrue($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals($original));
    }

    #[Test]
    public function testRegisteredTermWinsWhenItIsLongerThanWhatIsLeft(): void
    {
        // Same setup, but a registered term of 60 days outruns whatever the original has left.
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', 60, 5),
            $this->adjournmentEnd('2025-02-10', AdjournmentEndReason::Event),
        ]));

        // Sixty days starting on the end day means the last day is 2025-04-10.
        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate(CalendarDate::create('2025-04-10')),
            ),
        );
    }

    #[Test]
    public function testOriginalDeadlineOnTheEndDayStillCountsAsOneRemainingDay(): void
    {
        $original = $this->originalDecisionDeadline();

        // Ending the adjournment on the original deadline itself leaves exactly one day of that
        // term, which ties with the registered day and keeps the deadline where it was.
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', 1, 1),
            $this->adjournmentEnd($original->toDateString(), AdjournmentEndReason::Event),
        ]));

        $this->assertTrue($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals($original));
    }

    #[Test]
    public function testOriginalTermThatExpiredTheDayBeforeContributesNothing(): void
    {
        $endDate = $this->originalDecisionDeadline()->addDay();

        // One day past the original deadline there is nothing left of it, so the registered term
        // of a single day runs on its own and the end day itself becomes the deadline.
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', 1, 1),
            $this->adjournmentEnd($endDate->toDateString(), AdjournmentEndReason::Event),
        ]));

        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate($endDate),
            ),
        );
    }

    #[Test]
    public function testNoRegisteredTermAndAnExpiredOriginalTermLeavesTheDeadlineEmpty(): void
    {
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', null, null),
            $this->adjournmentEnd($this->originalDecisionDeadline()->addDay()->toDateString(), AdjournmentEndReason::Event),
        ]));

        $this->assertNull($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD));
    }

    #[Test]
    public function testDecisionTermWaitsForTheObjectionPeriodToFinish(): void
    {
        // The objection period runs 2025-01-02 up to and including 2025-02-12 (42 days), and the
        // adjournment ends inside it. The new decision term may only start the day after.
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
            $this->adjournment('2025-01-10', 10, 10),
            $this->adjournmentEnd('2025-01-20', AdjournmentEndReason::Event),
        ]));

        $lastObjectionDay = $derivedState->getCalendar()
            ->filter(static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::OBJECTION_PERIOD->value)
            ->sortByDesc(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->first();

        $firstDecisionDay = $derivedState->getCalendar()
            ->filter(static fn(EventCalendarDay $day): bool => $day->isFirstDayOfBudget
                && $day->applicableTerm === TermType::DECISION_PERIOD->value)
            ->first();

        $this->assertTrue($firstDecisionDay->date->equals($lastObjectionDay->date->addDay()));
    }

    #[Test]
    public function testObjectionPeriodKeepsRunningThroughTheAdjournment(): void
    {
        $withoutAdjournment = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
        ]));

        $withAdjournment = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
            $this->adjournment('2025-01-10', 10, 10),
            $this->adjournmentEnd('2025-01-20', AdjournmentEndReason::Event),
        ]));

        $this->assertTrue(
            $withAdjournment->deadlineDateForTerm(TermType::OBJECTION_PERIOD)->equals(
                $withoutAdjournment->deadlineDateForTerm(TermType::OBJECTION_PERIOD),
            ),
        );
    }

    #[Test]
    public function testSuspensionInsideTheObjectionPeriodCostsDaysWithoutPausingIt(): void
    {
        $withoutSuspension = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
        ]));

        $withSuspension = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
                suspensionType: SuspensionType::SUSPENSION,
            ),
        ]));

        // The bezwaartermijn is untouched...
        $this->assertTrue(
            $withSuspension->deadlineDateForTerm(TermType::OBJECTION_PERIOD)->equals(
                $withoutSuspension->deadlineDateForTerm(TermType::OBJECTION_PERIOD),
            ),
        );

        // ...but the suspended days are still counted.
        $this->assertSame(5, $withSuspension->getCalendar()->totalDaysSuspended);
        $this->assertSame(0, $withoutSuspension->getCalendar()->totalDaysSuspended);
    }

    #[Test]
    public function testAdjournmentDaysAreFlaggedUpToButNotIncludingTheEndDay(): void
    {
        $calendar = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 42),
            $this->adjournment('2025-02-01', 5, 5),
            $this->adjournmentEnd('2025-02-05', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-02-01'))->isUnspecifiedAdjournment);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-02-04'))->isUnspecifiedAdjournment);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-02-05'))->isUnspecifiedAdjournment);
    }

    #[Test]
    public function testEachCycleRestartsTheDecisionTerm(): void
    {
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 20),
            $this->adjournment('2025-02-01', 10, 10),
            $this->adjournmentEnd('2025-02-10', AdjournmentEndReason::Event),
            $this->adjournment('2025-03-01', 30, 30),
            $this->adjournmentEnd('2025-03-10', AdjournmentEndReason::Event),
        ]));

        // The second cycle wins: 30 days from 2025-03-10 ends on 2025-04-08.
        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate(CalendarDate::create('2025-04-08')),
            ),
        );
    }

    #[Test]
    public function testDeadlineLandingOnAWeekendMovesToTheNextWorkingDay(): void
    {
        // 2025-06-07 is a Saturday, so the deadline shifts to Monday 2025-06-09.
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 20),
            $this->adjournment('2025-02-01', 5, 5),
            $this->adjournmentEnd('2025-06-03', AdjournmentEndReason::Event),
        ]));

        $deadline = $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD);

        $this->assertTrue($deadline->equals(CalendarDate::create('2025-06-09')));
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2025-06-07'))->isATW);
    }

    /**
     * @param Collection<int, PetitionEventData> $events
     */
    private function build(Collection $events): DerivedState
    {
        return new DerivedState()
            ->withUnspecifiedAdjournmentMode(UnspecifiedAdjournmentMode::CONTINUES_TERMS)
            ->addEvents($events)
            ->buildCalendar();
    }

    /**
     * A petition whose decision term is long enough to still be running in February.
     *
     * @return Collection<int, PetitionEventData>
     */
    private function longDecisionTermEvents(): Collection
    {
        return collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 42),
        ]);
    }

    private function originalDecisionDeadline(): CalendarDate
    {
        return $this->build($this->longDecisionTermEvents())->deadlineDateForTerm(TermType::DECISION_PERIOD);
    }

    /**
     * @return Collection<int, PetitionEventData>
     */
    private function baseEvents(): Collection
    {
        return collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 42),
        ]);
    }

    private function primaryDecision(string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }

    private function receiptOfObjection(string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }

    private function adjournment(string $date, ?int $duration, ?int $withdrawalDuration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
            withdrawalDuration: $withdrawalDuration,
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
