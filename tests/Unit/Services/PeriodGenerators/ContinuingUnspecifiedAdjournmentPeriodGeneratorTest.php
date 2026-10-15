<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\AdjournmentEndReason;
use App\Enums\DecisionPeriodType;
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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function collect;
use function in_array;

class ContinuingUnspecifiedAdjournmentPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    /**
     * The original decision term runs from 2025-03-04 up to Monday 2025-04-14, the adjournment starts on 2025-03-10.
     * Every new term starts on the day the adjournment ends
     *
     * @return array<string, array{
     *     end: string,
     *     reason: AdjournmentEndReason,
     *     term: int|null,
     *     withdrawalTerm: int|null,
     *     holidays: list<string>,
     *     newTermDays: list<string>,
     *     newTermBudgetDays: list<string>,
     *     atwDays: list<string>,
     *     deadline: string,
     *     lastDaysOfBudget: list<string>,
     *     appliedTerm: int|null,
     *     originalDeadlines: list<string>,
     * }>
     */
    public static function newTermProvider(): array
    {
        return [
            'a new term ending on a working day' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Event,
                'term' => 42,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => self::dateRange('2025-04-01', '2025-05-12'),
                'newTermBudgetDays' => self::dateRange('2025-04-01', '2025-05-12'),
                'atwDays' => [],
                'deadline' => '2025-05-12',
                'lastDaysOfBudget' => ['2025-05-12'],
                'appliedTerm' => 42,
                'originalDeadlines' => ['2025-04-14'],
            ],
            'a new term ending on a Saturday moves to Monday' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Event,
                'term' => 40,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => self::dateRange('2025-04-01', '2025-05-12'),
                'newTermBudgetDays' => self::dateRange('2025-04-01', '2025-05-10'),
                'atwDays' => ['2025-05-10', '2025-05-11'],
                'deadline' => '2025-05-12',
                'lastDaysOfBudget' => ['2025-05-12'],
                'appliedTerm' => 40,
                'originalDeadlines' => ['2025-04-14'],
            ],
            'a new term ending on a public holiday moves to the next day' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Event,
                'term' => 42,
                'withdrawalTerm' => 28,
                'holidays' => ['2025-05-12'],
                'newTermDays' => self::dateRange('2025-04-01', '2025-05-13'),
                'newTermBudgetDays' => self::dateRange('2025-04-01', '2025-05-12'),
                'atwDays' => ['2025-05-12'],
                'deadline' => '2025-05-13',
                'lastDaysOfBudget' => ['2025-05-13'],
                'appliedTerm' => 42,
                'originalDeadlines' => ['2025-04-14'],
            ],
            // The adjournment waits from 04-15 to 04-21; the original deadline stops being one
            'a new term starting after the original deadline' => [
                'end' => '2025-04-22',
                'reason' => AdjournmentEndReason::Event,
                'term' => 14,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => self::dateRange('2025-04-22', '2025-05-05'),
                'newTermBudgetDays' => self::dateRange('2025-04-22', '2025-05-05'),
                'atwDays' => [],
                'deadline' => '2025-05-05',
                'lastDaysOfBudget' => ['2025-04-14', '2025-05-05'],
                'appliedTerm' => 14,
                'originalDeadlines' => ['2025-04-14'],
            ],
            'a withdrawal uses the withdrawal term' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Withdrawal,
                'term' => 42,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => self::dateRange('2025-04-01', '2025-04-28'),
                'newTermBudgetDays' => self::dateRange('2025-04-01', '2025-04-28'),
                'atwDays' => [],
                'deadline' => '2025-04-28',
                'lastDaysOfBudget' => ['2025-04-28'],
                'appliedTerm' => 28,
                'originalDeadlines' => ['2025-04-14'],
            ],
            'a new term ending on the original deadline keeps the original term' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Event,
                'term' => 14,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => [],
                'newTermBudgetDays' => [],
                'atwDays' => [],
                'deadline' => '2025-04-14',
                'lastDaysOfBudget' => ['2025-04-14'],
                'appliedTerm' => null,
                'originalDeadlines' => [],
            ],
            'a new term ending the day after the original deadline replaces it' => [
                'end' => '2025-04-01',
                'reason' => AdjournmentEndReason::Event,
                'term' => 15,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => self::dateRange('2025-04-01', '2025-04-15'),
                'newTermBudgetDays' => self::dateRange('2025-04-01', '2025-04-15'),
                'atwDays' => [],
                'deadline' => '2025-04-15',
                'lastDaysOfBudget' => ['2025-04-15'],
                'appliedTerm' => 15,
                'originalDeadlines' => ['2025-04-14'],
            ],
            'a withdrawal without a withdrawal term keeps the original term' => [
                'end' => '2025-04-22',
                'reason' => AdjournmentEndReason::Withdrawal,
                'term' => 42,
                'withdrawalTerm' => null,
                'holidays' => [],
                'newTermDays' => [],
                'newTermBudgetDays' => [],
                'atwDays' => [],
                'deadline' => '2025-04-14',
                'lastDaysOfBudget' => ['2025-04-14'],
                'appliedTerm' => null,
                'originalDeadlines' => [],
            ],
            'an event without a registered term keeps the original term' => [
                'end' => '2025-04-22',
                'reason' => AdjournmentEndReason::Event,
                'term' => null,
                'withdrawalTerm' => 28,
                'holidays' => [],
                'newTermDays' => [],
                'newTermBudgetDays' => [],
                'atwDays' => [],
                'deadline' => '2025-04-14',
                'lastDaysOfBudget' => ['2025-04-14'],
                'appliedTerm' => null,
                'originalDeadlines' => [],
            ],
        ];
    }

    /**
     * @param list<string> $holidays
     * @param list<string> $newTermDays
     * @param list<string> $newTermBudgetDays
     * @param list<string> $atwDays
     * @param list<string> $lastDaysOfBudget
     * @param list<string> $originalDeadlines
     */
    #[Test]
    #[DataProvider('newTermProvider')]
    public function testNewTerm(
        string $end,
        AdjournmentEndReason $reason,
        ?int $term,
        ?int $withdrawalTerm,
        array $holidays,
        array $newTermDays,
        array $newTermBudgetDays,
        array $atwDays,
        string $deadline,
        array $lastDaysOfBudget,
        ?int $appliedTerm,
        array $originalDeadlines,
    ): void {
        FakeLegalTermDeadlineCalculator::bind($this->app, $holidays);

        $calendar = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-03-03', 42),
            $this->adjournment('2025-03-10', $term, $withdrawalTerm),
            $this->adjournmentEnd($end, $reason),
        ]))->getCalendar();

        $isNewTermDay = static fn(EventCalendarDay $day): bool => in_array(
            $day->decisionPeriodType,
            [DecisionPeriodType::EVENT, DecisionPeriodType::WITHDRAWAL],
            true,
        );
        $decisionPeriod = TermType::DECISION_PERIOD;

        $this->assertSame($newTermDays, $this->datesWhere($calendar, $isNewTermDay));
        $this->assertSame(
            $newTermBudgetDays,
            $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $isNewTermDay($day) && $day->isBudgetDay),
        );
        $this->assertSame(
            $newTermDays === [] ? [] : [$end],
            $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $isNewTermDay($day) && $day->isFirstDayOfBudget),
        );
        $this->assertSame($atwDays, $this->atwDates($calendar, $decisionPeriod));
        $this->assertSame([$deadline], $this->deadlineDates($calendar, $decisionPeriod));
        $this->assertSame($lastDaysOfBudget, $this->lastDayOfBudgetDates($calendar, $decisionPeriod));
        $this->assertSame($appliedTerm, $calendar->findDay(CalendarDate::create($end))?->appliedUnspecifiedAdjournmentTerm);
        $this->assertSame(
            $originalDeadlines,
            $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isOriginalDeadline),
        );
    }

    #[Test]
    public function testAdjournmentWithoutADecisionTermOrARegisteredTermAddsNothing(): void
    {
        FakeLegalTermDeadlineCalculator::bind($this->app);

        $calendar = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->adjournment('2025-03-10', null, null),
            $this->adjournmentEnd('2025-03-17', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $this->assertSame([], $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::DECISION_PERIOD->value,
        ));
    }

    #[Test]
    public function testTermsKeepRunningSoTheBudgetIsNotShiftedForward(): void
    {
        $withoutAdjournment = $this->build($this->baseEvents());

        // A short registered term keeps the original decision deadline, because the original term
        // has not expired yet and is the longer of the two
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
    public function testOpenAdjournmentKeepsTheOriginalDecisionDeadline(): void
    {
        $original = $this->build($this->baseEvents())->deadlineDateForTerm(TermType::DECISION_PERIOD);

        $derivedState = $this->build(collect([
            ...$this->baseEvents()->all(),
            $this->adjournment('2025-03-01', 42, 42),
        ]));

        $this->assertTrue($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals($original));
        $this->assertTrue($derivedState->deadlineDate()->equals($original));
    }

    #[Test]
    public function testEndDayIsTheFirstDayOfTheNewDecisionTerm(): void
    {
        // The original term ran out on 2025-03-05, so only the registered term is left
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-01-10', 10),
            $this->adjournment('2025-03-01', 10, 20),
            $this->adjournmentEnd('2025-06-02', AdjournmentEndReason::Event),
        ]));

        $firstDay = $derivedState->getCalendar()->findDay(CalendarDate::create('2025-06-02'));

        $this->assertTrue($firstDay->isFirstDayOfBudget);
        $this->assertSame(TermType::DECISION_PERIOD->value, $firstDay->applicableTerm);

        // Ten days starting on the end day means the last day is 2025-06-11
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

        // Twenty days starting on the end day means the last day is 2025-06-21
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
        // the registered term of 5 and the deadline does not move
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
        // Same setup, but a registered term of 60 days outruns whatever the original has left
        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', 60, 5),
            $this->adjournmentEnd('2025-02-10', AdjournmentEndReason::Event),
        ]));

        // Sixty days starting on the end day means the last day is 2025-04-10
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
        // term, which ties with the registered day and keeps the deadline where it was
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
        // of a single day runs on its own and the end day itself becomes the deadline
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
    public function testNoRegisteredTermKeepsTheExpiredOriginalDeadline(): void
    {
        $original = $this->originalDecisionDeadline();

        $derivedState = $this->build(collect([
            ...$this->longDecisionTermEvents()->all(),
            $this->adjournment('2025-02-01', null, null),
            $this->adjournmentEnd($original->addDay()->toDateString(), AdjournmentEndReason::Event),
        ]));

        $this->assertTrue($derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals($original));
        $this->assertFalse($derivedState->getCalendar()->findDay($original)->isOriginalDeadline);
    }

    #[Test]
    public function testDecisionTermWaitsForTheObjectionPeriodToFinish(): void
    {
        // The objection period runs 2025-01-02 up to and including 2025-02-12 (42 days), and the
        // adjournment ends inside it. The new decision term may only start the day after, and is
        // long enough to outrun the original one.
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 42),
            $this->receiptOfObjection('2025-02-20', 10),
            $this->adjournment('2025-01-10', 60, 60),
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

        // ...but the suspended days are still counted
        $this->assertSame(5, $withSuspension->getCalendar()->totalDaysSuspended);
        $this->assertSame(0, $withoutSuspension->getCalendar()->totalDaysSuspended);
    }

    #[Test]
    public function testAdjournmentLeavesTheDaysOfTheRunningTermsUntouched(): void
    {
        $calendar = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 42),
            $this->adjournment('2025-02-01', 5, 5),
            $this->adjournmentEnd('2025-02-05', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-02-01'))->isUnspecifiedAdjournment);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-02-04'))->isUnspecifiedAdjournment);
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

        // The second cycle wins: 30 days from 2025-03-10 ends on 2025-04-08
        $this->assertTrue(
            $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)->equals(
                LegalTermDeadlineCalculator::calculate(CalendarDate::create('2025-04-08')),
            ),
        );
    }

    #[Test]
    public function testDeadlineLandingOnAWeekendMovesToTheNextWorkingDay(): void
    {
        // 2025-06-07 is a Saturday, so the deadline shifts to Monday 2025-06-09
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->receiptOfObjection('2025-01-10', 20),
            $this->adjournment('2025-02-01', 5, 5),
            $this->adjournmentEnd('2025-06-03', AdjournmentEndReason::Event),
        ]));

        $deadline = $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD);

        $this->assertTrue($deadline->equals(CalendarDate::create('2025-06-09')));
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2025-06-07'))->isATW);
        $this->assertSame(
            DecisionPeriodType::EVENT,
            $derivedState->getCalendar()->findDay(CalendarDate::create('2025-06-07'))->decisionPeriodType,
        );
        $this->assertSame(DecisionPeriodType::EVENT, $derivedState->getCalendar()->findDay($deadline)->decisionPeriodType);
    }

    /**
     * The original decision term runs 2026-04-02 up to and including Thursday 2026-04-30.
     * The adjournment registers 14 days after an event and 28 days after a withdrawal.
     */
    #[Test]
    public function testOpenAdjournmentWithinTheDecisionTermChangesNothing(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-15'));

        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
        ]));

        $this->assertDecisionDeadline('2026-04-30', $derivedState);
        $this->assertTrue($derivedState->deadlineDate()->equals(CalendarDate::create('2026-04-30')));
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-30'))->isDeadline);
        $this->assertNull($derivedState->getCalendar()->findDay(CalendarDate::create('2026-05-01')));
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-15'))->isOpenUnspecifiedAdjournment);
        $this->assertFalse($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-14'))->isOpenUnspecifiedAdjournment);
    }

    #[Test]
    public function testOpenAdjournmentFoldsTheSameAsWithoutIt(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-15'));

        $withoutAdjournment = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
        ]))->getCalendar();

        $withAdjournment = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
        ]))->getCalendar();

        // Today and tomorrow must look alike, or tomorrow shows up as a change in the folded view
        foreach (['2026-04-14', '2026-04-15', '2026-04-16', '2026-04-29'] as $date) {
            $this->assertSame(
                $withoutAdjournment->findDay(CalendarDate::create($date))->summary(),
                $withAdjournment->findDay(CalendarDate::create($date))->summary(),
            );
        }
    }

    #[Test]
    public function testAdjournmentEndingEarlyEnoughKeepsTheOriginalTerm(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-25'));

        // 14 days from 2026-04-07 end on 2026-04-20, well before the original last day
        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-04-07', AdjournmentEndReason::Event),
        ]));

        $originalLastDay = $derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-30'));

        $this->assertDecisionDeadline('2026-04-30', $derivedState);
        $this->assertTrue($originalLastDay->isDeadline);
        $this->assertFalse($originalLastDay->isOriginalDeadline);
        $this->assertFalse($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-07'))->isFirstDayOfBudget);
        $this->assertNull($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-07'))->appliedUnspecifiedAdjournmentTerm);
    }

    #[Test]
    public function testAdjournmentEndingByEventExtendsTheDecisionTerm(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-25'));

        // 14 days from 2026-04-21 end on Monday 2026-05-04
        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-04-21', AdjournmentEndReason::Event),
        ]));

        $originalLastDay = $derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-30'));

        $this->assertDecisionDeadline('2026-05-04', $derivedState);
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-05-04'))->isDeadline);
        $this->assertTrue($originalLastDay->isOriginalDeadline);
        $this->assertFalse($originalLastDay->isDeadline);
        $this->assertSame(14, $derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-21'))->appliedUnspecifiedAdjournmentTerm);
        $this->assertSame(TermType::DECISION_PERIOD->value, $originalLastDay->applicableTerm);
        $this->assertSame(DecisionPeriodType::EVENT, $originalLastDay->decisionPeriodType);
        $this->assertSame(
            DecisionPeriodType::EVENT,
            $derivedState->getCalendar()->findDay(CalendarDate::create('2026-05-04'))->decisionPeriodType,
        );
        $this->assertFalse($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-25'))->isOpenUnspecifiedAdjournment);
    }

    #[Test]
    public function testExtendingAfterAVerdagingLabelsTheResumedStatutoryLastDay(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-10'));

        // The verdaging takes 04-10 to 04-14, the statutory term continues up to Tuesday 05-05 and
        // the new term runs from 05-01 to Thursday 05-14
        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->verdaging('2026-04-10', 5),
            $this->adjournment('2026-04-15', 14, 28),
            $this->adjournmentEnd('2026-05-01', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-05-14'))->isDeadline);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-05-05'))->isOriginalDeadline);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2026-04-14'))->isOriginalDeadline);
    }

    #[Test]
    public function testNewTermOverlappingTheOriginalKeepsTheOriginalTermBeforeIt(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-25'));

        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-04-21', AdjournmentEndReason::Event),
        ]))->getCalendar();

        foreach (['2026-04-02', '2026-04-10', '2026-04-20'] as $date) {
            $day = $calendar->findDay(CalendarDate::create($date));

            $this->assertSame(TermType::DECISION_PERIOD->value, $day->applicableTerm);
            $this->assertSame(DecisionPeriodType::STATUTORY, $day->decisionPeriodType);
            $this->assertTrue($day->isBudgetDay);
        }

        $firstNewDay = $calendar->findDay(CalendarDate::create('2026-04-21'));

        $this->assertTrue($firstNewDay->isFirstDayOfBudget);
        $this->assertSame(DecisionPeriodType::EVENT, $firstNewDay->decisionPeriodType);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-04-30'))->isOriginalDeadline);
    }

    #[Test]
    public function testSecondAdjournmentDoesNotLabelTheLastDayOfTheFirstOneAsStatutory(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-06-03'));

        // The first new term runs from 2026-05-12 to Monday 2026-05-25, the second one from
        // 2026-06-02 to Monday 2026-06-15
        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-05-12', AdjournmentEndReason::Event),
            $this->adjournment('2026-05-20', 14, 28),
            $this->adjournmentEnd('2026-06-02', AdjournmentEndReason::Event),
        ]));

        $originalDeadlines = $derivedState->getCalendar()
            ->filter(static fn(EventCalendarDay $day): bool => $day->isOriginalDeadline)
            ->map(static fn(EventCalendarDay $day): string => $day->date->toDateString())
            ->values()
            ->all();

        $this->assertDecisionDeadline('2026-06-15', $derivedState);
        $this->assertSame(['2026-04-30'], $originalDeadlines);
    }

    #[Test]
    public function testNewTermStartingOnTheOriginalDeadlineKeepsItsAtwDays(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-05'));

        // 31 days end on Saturday 2026-05-02, so the original deadline moves to Monday 2026-05-04
        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 31),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-05-04', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $atwDay = $calendar->findDay(CalendarDate::create('2026-05-02'));

        $this->assertSame(TermType::DECISION_PERIOD->value, $atwDay->applicableTerm);
        $this->assertSame(DecisionPeriodType::STATUTORY, $atwDay->decisionPeriodType);
        $this->assertTrue($atwDay->isATW);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-05-04'))->isFirstDayOfBudget);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-05-04'))->isOriginalDeadline);
    }

    #[Test]
    public function testAdjournmentEndingByWithdrawalUsesTheWithdrawalDays(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-04-25'));

        // 28 days from 2026-04-21 end on Monday 2026-05-18
        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-04-21', AdjournmentEndReason::Withdrawal),
        ]));

        $this->assertDecisionDeadline('2026-05-18', $derivedState);
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-30'))->isOriginalDeadline);
        $this->assertSame(
            DecisionPeriodType::STATUTORY,
            $derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-10'))->decisionPeriodType,
        );
        $this->assertSame(
            DecisionPeriodType::WITHDRAWAL,
            $derivedState->getCalendar()->findDay(CalendarDate::create('2026-05-18'))->decisionPeriodType,
        );
    }

    #[Test]
    public function testOpenAdjournmentAfterTheDecisionTermWaitsUntilToday(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
        ]));

        $calendar = $derivedState->getCalendar();

        $this->assertDecisionDeadline('2026-04-30', $derivedState);
        $this->assertTrue($derivedState->deadlineDate()->equals(CalendarDate::create('2026-04-30')));
        $this->assertSame(TermType::DECISION_PERIOD->value, $calendar->findDay(CalendarDate::create('2026-04-30'))->applicableTerm);
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-01')));
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-20')));
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-05-20'))->isOpenUnspecifiedAdjournment);
        $this->assertNull($calendar->findDay(CalendarDate::create('2026-05-21')));
        $this->assertSame(
            $calendar->findDay(CalendarDate::create('2026-05-01'))->summary(),
            $calendar->findDay(CalendarDate::create('2026-05-19'))->summary(),
        );
    }

    #[Test]
    public function testAdjournmentEndingAfterTheDecisionTermStartsANewOne(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-27'));

        // 14 days from 2026-05-26 end on Monday 2026-06-08
        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-05-26', AdjournmentEndReason::Event),
        ]));

        $calendar = $derivedState->getCalendar();
        $firstNewDay = $calendar->findDay(CalendarDate::create('2026-05-26'));

        $this->assertDecisionDeadline('2026-06-08', $derivedState);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2026-04-30'))->isOriginalDeadline);
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-01')));
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-25')));
        $this->assertSame(TermType::DECISION_PERIOD->value, $firstNewDay->applicableTerm);
        $this->assertSame(DecisionPeriodType::EVENT, $firstNewDay->decisionPeriodType);
        $this->assertSame(DecisionPeriodType::STATUTORY, $calendar->findDay(CalendarDate::create('2026-04-30'))->decisionPeriodType);
        $this->assertTrue($firstNewDay->isFirstDayOfBudget);
        $this->assertFalse($firstNewDay->isUnspecifiedAdjournment);
    }

    #[Test]
    public function testAdjournmentEndingTheDayAfterTheDecisionTermHasNoWaitingDays(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-04'));

        $derivedState = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-05-01', AdjournmentEndReason::Event),
        ]));

        $this->assertDecisionDeadline('2026-05-14', $derivedState);
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-05-01'))->isFirstDayOfBudget);
        $this->assertTrue($derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-30'))->isOriginalDeadline);
        $this->assertSame(
            TermType::DECISION_PERIOD->value,
            $derivedState->getCalendar()->findDay(CalendarDate::create('2026-04-02'))->applicableTerm,
        );
        $this->assertEmpty($derivedState->getCalendar()->filter(
            static fn(EventCalendarDay $day): bool => $day->decisionPeriodType === DecisionPeriodType::EXTENDED,
        ));
    }

    #[Test]
    public function testAdjournmentStartedAfterTheDecisionTermWaitsFromItsOwnStart(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-05-10', 14, 28),
        ]))->getCalendar();

        $this->assertNull($calendar->findDay(CalendarDate::create('2026-05-09')));
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-10')));
    }

    #[Test]
    public function testWaitingDaysLeaveOtherTermsAlone(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        // The "ingebrekestelling" runs 2026-05-06 up to and including 2026-05-19
        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2026-05-05'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
        ]))->getCalendar();

        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-05')));
        $this->assertSame(TermType::NOTICE_OF_DEFAULT->value, $calendar->findDay(CalendarDate::create('2026-05-06'))->applicableTerm);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2026-05-06'))->isUnspecifiedAdjournment);
        $this->assertWaiting($calendar->findDay(CalendarDate::create('2026-05-20')));
    }

    #[Test]
    public function testAdjournmentWithoutADecisionTermOnlyStartsANewOne(): void
    {
        $this->travelTo(CarbonImmutable::parse('2025-03-01'));

        // Without a receipt there is no decision term yet: nothing to wait on and nothing to keep
        $derivedState = $this->build(collect([
            $this->primaryDecision('2025-01-01', 10),
            $this->adjournment('2025-01-20', 10, 10),
            $this->adjournmentEnd('2025-02-10', AdjournmentEndReason::Event),
        ]));

        $this->assertDecisionDeadline('2025-02-19', $derivedState);
        $this->assertEmpty($derivedState->getCalendar()->filter(
            static fn(EventCalendarDay $day): bool => $day->decisionPeriodType === DecisionPeriodType::EXTENDED
                || $day->isOriginalDeadline,
        ));
    }

    #[Test]
    public function testOriginalDeadlineIsPartOfTheCalendarSummary(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-27'));

        $calendar = $this->build(collect([
            $this->receiptOfObjection('2026-04-01', 29),
            $this->adjournment('2026-04-01', 14, 28),
            $this->adjournmentEnd('2026-05-26', AdjournmentEndReason::Event),
        ]))->getCalendar();

        $this->assertStringContainsString('isOriginalDeadline: 1', $calendar->findDay(CalendarDate::create('2026-04-30'))->summary());
    }

    private function assertDecisionDeadline(string $expected, DerivedState $derivedState): void
    {
        $this->assertSame($expected, $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)?->toDateString());
    }

    private function assertWaiting(?EventCalendarDay $day): void
    {
        $this->assertInstanceOf(EventCalendarDay::class, $day);
        $this->assertSame(TermType::DECISION_PERIOD->value, $day->applicableTerm);
        $this->assertSame(DecisionPeriodType::EXTENDED, $day->decisionPeriodType);
        $this->assertTrue($day->isUnspecifiedAdjournment);
        $this->assertFalse($day->isDeadline);
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
     * A petition whose decision term is long enough to still be running in February
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

    private function verdaging(string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::ADJOURNMENT,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
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
