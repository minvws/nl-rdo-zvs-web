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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function collect;

class UnspecifiedAdjournmentPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    /**
     * Today is 2025-01-25. Every end is a [date, reason] pair.
     *
     * @return array<string, array{
     *     starts: list<string>,
     *     ends: list<array{string, AdjournmentEndReason}>,
     *     frozenDays: list<string>,
     *     withdrawalDays: list<string>,
     * }>
     */
    public static function freezeProvider(): array
    {
        return [
            'the end day itself counts towards the term again' => [
                'starts' => ['2025-01-10'],
                'ends' => [
                    ['2025-01-13', AdjournmentEndReason::Event],
                ],
                'frozenDays' => self::dateRange('2025-01-10', '2025-01-12'),
                'withdrawalDays' => [],
            ],
            'an end the day after the start freezes a single day' => [
                'starts' => ['2025-01-10'],
                'ends' => [
                    ['2025-01-11', AdjournmentEndReason::Event],
                ],
                'frozenDays' => ['2025-01-10'],
                'withdrawalDays' => [],
            ],
            'an end on the start day freezes nothing' => [
                'starts' => ['2025-01-10'],
                'ends' => [
                    ['2025-01-10', AdjournmentEndReason::Event],
                ],
                'frozenDays' => [],
                'withdrawalDays' => [],
            ],
            'a withdrawal is marked on the end day' => [
                'starts' => ['2025-01-10'],
                'ends' => [
                    ['2025-01-13', AdjournmentEndReason::Withdrawal],
                ],
                'frozenDays' => self::dateRange('2025-01-10', '2025-01-12'),
                'withdrawalDays' => ['2025-01-13'],
            ],
            'an open adjournment freezes up to today' => [
                'starts' => ['2025-01-20'],
                'ends' => [],
                'frozenDays' => self::dateRange('2025-01-20', '2025-01-24'),
                'withdrawalDays' => [],
            ],
            'an open adjournment started today freezes nothing yet' => [
                'starts' => ['2025-01-25'],
                'ends' => [],
                'frozenDays' => [],
                'withdrawalDays' => [],
            ],
            'every cycle freezes its own range' => [
                'starts' => ['2025-01-10', '2025-01-20'],
                'ends' => [
                    ['2025-01-12', AdjournmentEndReason::Event],
                    ['2025-01-22', AdjournmentEndReason::Withdrawal],
                ],
                'frozenDays' => ['2025-01-10', '2025-01-11', '2025-01-20', '2025-01-21'],
                'withdrawalDays' => ['2025-01-22'],
            ],
            'starts and ends are paired in date order' => [
                'starts' => ['2025-01-20', '2025-01-10'],
                'ends' => [
                    ['2025-01-22', AdjournmentEndReason::Event],
                    ['2025-01-12', AdjournmentEndReason::Event],
                ],
                'frozenDays' => ['2025-01-10', '2025-01-11', '2025-01-20', '2025-01-21'],
                'withdrawalDays' => [],
            ],
            'a second cycle that is still open' => [
                'starts' => ['2025-01-10', '2025-01-22'],
                'ends' => [
                    ['2025-01-12', AdjournmentEndReason::Event],
                ],
                'frozenDays' => ['2025-01-10', '2025-01-11', '2025-01-22', '2025-01-23', '2025-01-24'],
                'withdrawalDays' => [],
            ],
            'a freeze crossing the turn of the year' => [
                'starts' => ['2024-12-30'],
                'ends' => [
                    ['2025-01-02', AdjournmentEndReason::Event],
                ],
                'frozenDays' => ['2024-12-30', '2024-12-31', '2025-01-01'],
                'withdrawalDays' => [],
            ],
        ];
    }

    /**
     * @param list<string> $starts
     * @param list<array{string, AdjournmentEndReason}> $ends
     * @param list<string> $frozenDays
     * @param list<string> $withdrawalDays
     */
    #[Test]
    #[DataProvider('freezeProvider')]
    public function testFreezesTheDaysOfEveryAdjournment(
        array $starts,
        array $ends,
        array $frozenDays,
        array $withdrawalDays,
    ): void {
        $this->travelTo(CarbonImmutable::parse('2025-01-25'));
        $calendar = new EventCalendar();
        $events = collect([
            ...collect($starts)->map(fn(string $start): PetitionEventData => $this->adjournment($start)),
            ...collect($ends)->map(fn(array $end): PetitionEventData => $this->adjournmentEnd($end[0], $end[1])),
        ]);

        new UnspecifiedAdjournmentPeriodGenerator()->generate($events, $calendar);

        $this->assertSame($frozenDays, $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isUnspecifiedAdjournment,
        ));
        $this->assertSame($withdrawalDays, $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->isUnspecifiedAdjournmentWithdrawal,
        ));
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
        return new DerivedState()->addEvents($events)->buildCalendar();
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
