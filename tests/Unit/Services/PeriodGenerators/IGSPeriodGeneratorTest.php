<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\IGSPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function array_keys;
use function collect;

class IGSPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    protected function setUp(): void
    {
        parent::setUp();

        FakeLegalTermDeadlineCalculator::bind($this->app);
    }

    /**
     * @return array<string, array{PetitionEventData}>
     */
    public static function withoutTermProvider(): array
    {
        return [
            'no notice of default' => [self::primaryDecision('2025-01-01', 30)],
            'a term of zero days' => [self::noticeOfDefault('2025-01-01', 0)],
            'no term' => [self::noticeOfDefault('2025-01-01', null)],
        ];
    }

    #[Test]
    #[DataProvider('withoutTermProvider')]
    public function testGeneratesNothingWithoutATerm(PetitionEventData $event): void
    {
        $calendar = new EventCalendar();

        new IGSPeriodGenerator()->generate(collect([$event]), $calendar);

        $this->assertEmpty($calendar->all());
    }

    /**
     * 2025-01-01 is a Wednesday; 01-04/05 and 01-11/12 are weekends.
     *
     * @return array<string, array{
     *     date: string,
     *     term: int,
     *     penalties: list<array{int, int}>,
     *     holidays: list<string>,
     *     budgetDays: list<string>,
     *     atwDays: list<string>,
     *     deadline: string,
     *     penaltyDays: array<string, int>,
     * }>
     */
    public static function termProvider(): array
    {
        return [
            'term ending on a working day' => [
                'date' => '2025-01-01',
                'term' => 2,
                'penalties' => [],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
                'penaltyDays' => [],
            ],
            'term ending on a Saturday moves to Monday' => [
                'date' => '2025-01-01',
                'term' => 3,
                'penalties' => [],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-04'),
                'atwDays' => ['2025-01-04', '2025-01-05'],
                'deadline' => '2025-01-06',
                'penaltyDays' => [],
            ],
            'term ending on a Sunday moves to Monday' => [
                'date' => '2025-01-01',
                'term' => 4,
                'penalties' => [],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-05'),
                'atwDays' => ['2025-01-05'],
                'deadline' => '2025-01-06',
                'penaltyDays' => [],
            ],
            'term ending on a public holiday moves to the next day' => [
                'date' => '2025-01-01',
                'term' => 5,
                'penalties' => [],
                'holidays' => ['2025-01-06'],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-06'),
                'atwDays' => ['2025-01-06'],
                'deadline' => '2025-01-07',
                'penaltyDays' => [],
            ],
            'the default term of two weeks' => [
                'date' => '2025-01-01',
                'term' => 14,
                'penalties' => [],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-15'),
                'atwDays' => [],
                'deadline' => '2025-01-15',
                'penaltyDays' => [],
            ],
            'penalties start the day after the deadline' => [
                'date' => '2025-01-01',
                'term' => 2,
                'penalties' => [
                    [150, 3],
                ],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
                'penaltyDays' => ['2025-01-04' => 150, '2025-01-05' => 150, '2025-01-06' => 150],
            ],
            'penalties start the day after a moved deadline' => [
                'date' => '2025-01-01',
                'term' => 3,
                'penalties' => [
                    [100, 2],
                ],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-04'),
                'atwDays' => ['2025-01-04', '2025-01-05'],
                'deadline' => '2025-01-06',
                'penaltyDays' => ['2025-01-07' => 100, '2025-01-08' => 100],
            ],
            'penalty tiers follow each other' => [
                'date' => '2025-01-01',
                'term' => 1,
                'penalties' => [
                    [200, 2],
                    [75, 2],
                ],
                'holidays' => [],
                'budgetDays' => ['2025-01-02'],
                'atwDays' => [],
                'deadline' => '2025-01-02',
                'penaltyDays' => ['2025-01-03' => 200, '2025-01-04' => 200, '2025-01-05' => 75, '2025-01-06' => 75],
            ],
        ];
    }

    /**
     * @param list<array{int, int}> $penalties [amount, duration] pairs
     * @param list<string> $holidays
     * @param list<string> $budgetDays
     * @param list<string> $atwDays
     * @param array<string, int> $penaltyDays
     */
    #[Test]
    #[DataProvider('termProvider')]
    public function testTerm(
        string $date,
        int $term,
        array $penalties,
        array $holidays,
        array $budgetDays,
        array $atwDays,
        string $deadline,
        array $penaltyDays,
    ): void {
        FakeLegalTermDeadlineCalculator::bind($this->app, $holidays);
        $calendar = new EventCalendar();

        new IGSPeriodGenerator()->generate(collect([
            self::noticeOfDefault($date, $term, collect($penalties)->map(
                static fn(array $penalty): PenaltyData => new PenaltyData(amount: $penalty[0], duration: $penalty[1]),
            )->all()),
        ]), $calendar);

        $noticeOfDefault = TermType::NOTICE_OF_DEFAULT;
        $this->assertSame($budgetDays, $this->budgetDates($calendar, $noticeOfDefault));
        // Penalty days never mark the first or last day of a budget.
        $this->assertSame(
            [$budgetDays[0]],
            $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isFirstDayOfBudget),
        );
        $this->assertSame(
            [],
            $this->datesWhere(
                $calendar,
                static fn(EventCalendarDay $day): bool => $day->isLastDayOfBudget && $day->applicableTerm === TermType::PENALTY_PERIOD->value,
            ),
        );
        $this->assertSame($atwDays, $this->atwDates($calendar, $noticeOfDefault));
        $this->assertSame([$deadline], $this->deadlineDates($calendar, $noticeOfDefault));
        $this->assertSame([$deadline], $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isDeadline));
        $this->assertSame($penaltyDays, $calendar
            ->filter(static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::PENALTY_PERIOD->value)
            ->mapWithKeys(static fn(EventCalendarDay $day): array => [$day->date->toDateString() => $day->penaltyTodayInEuros])
            ->sortKeys()
            ->all());

        foreach (array_keys($penaltyDays) as $penaltyDay) {
            $day = $calendar->findDay(CalendarDate::create($penaltyDay));
            $this->assertFalse($day->isBudgetDay, $penaltyDay);
            $this->assertSame($noticeOfDefault->value, $day->penaltySourceTerm, $penaltyDay);
        }
    }

    /**
     * Every notice of default has a term of two days; the expected dates are the first day of
     * every term that is generated.
     *
     * @return array<string, array{list<PetitionEventData>, list<string>}>
     */
    public static function withdrawalProvider(): array
    {
        return [
            'two notices of default without a withdrawal' => [
                [self::noticeOfDefault('2025-01-01', 2), self::noticeOfDefault('2025-02-01', 2)],
                ['2025-01-02', '2025-02-02'],
            ],
            'a withdrawn notice of default' => [
                [self::noticeOfDefault('2025-01-01', 2), self::withdrawal('2025-01-05')],
                [],
            ],
            'a new notice of default after a withdrawal' => [
                [self::noticeOfDefault('2025-01-01', 2), self::withdrawal('2025-01-05'), self::noticeOfDefault('2025-02-01', 2)],
                ['2025-02-02'],
            ],
            'every notice of default withdrawn' => [
                [
                    self::noticeOfDefault('2025-01-01', 2),
                    self::withdrawal('2025-01-05'),
                    self::noticeOfDefault('2025-02-01', 2),
                    self::withdrawal('2025-02-10'),
                ],
                [],
            ],
            'only the notice of default after the last withdrawal' => [
                [
                    self::noticeOfDefault('2025-01-01', 2),
                    self::withdrawal('2025-01-05'),
                    self::noticeOfDefault('2025-02-01', 2),
                    self::withdrawal('2025-02-10'),
                    self::noticeOfDefault('2025-03-01', 2),
                ],
                ['2025-03-02'],
            ],
            'events given out of order' => [
                [self::noticeOfDefault('2025-02-01', 2), self::withdrawal('2025-01-05'), self::noticeOfDefault('2025-01-01', 2)],
                ['2025-02-02'],
            ],
        ];
    }

    /**
     * @param list<PetitionEventData> $events
     * @param list<string> $expectedTermStarts
     */
    #[Test]
    #[DataProvider('withdrawalProvider')]
    public function testWithdrawalCancelsTheEarliestNoticeOfDefault(array $events, array $expectedTermStarts): void
    {
        $calendar = new EventCalendar();

        new IGSPeriodGenerator()->generate(collect($events), $calendar);

        $this->assertSame($expectedTermStarts, $this->firstDayOfBudgetDates($calendar, TermType::NOTICE_OF_DEFAULT));
    }

    /**
     * The decision period ends on Monday 2025-01-06.
     *
     * @return array<string, array{string, list<string>}>
     */
    public static function decisionPeriodProvider(): array
    {
        return [
            'received within the decision period' => [
                '2025-01-04',
                [],
            ],
            'received on the decision deadline' => [
                '2025-01-06',
                [],
            ],
            'received the day after the decision deadline' => [
                '2025-01-07',
                ['2025-01-08', '2025-01-09'],
            ],
            'received long after the decision deadline' => [
                '2025-03-03',
                ['2025-03-04', '2025-03-05'],
            ],
        ];
    }

    /**
     * @param list<string> $expectedBudgetDays
     */
    #[Test]
    #[DataProvider('decisionPeriodProvider')]
    public function testOnlyStartsAfterTheDecisionPeriod(string $date, array $expectedBudgetDays): void
    {
        $calendar = new EventCalendar();
        foreach (self::dateRange('2025-01-02', '2025-01-06') as $decisionDay) {
            $calendar->upsertDay(CalendarDate::create($decisionDay), [
                'applicableTerm' => TermType::DECISION_PERIOD->value,
                'isBudgetDay' => true,
                'isDeadline' => $decisionDay === '2025-01-06',
            ]);
        }

        new IGSPeriodGenerator()->generate(collect([self::noticeOfDefault($date, 2)]), $calendar);

        $this->assertSame($expectedBudgetDays, $this->budgetDates($calendar, TermType::NOTICE_OF_DEFAULT));
        $this->assertSame(self::dateRange('2025-01-02', '2025-01-06'), $this->budgetDates($calendar, TermType::DECISION_PERIOD));
    }

    #[Test]
    public function testPenaltyDataRejectsZeroDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty duration must be positive');

        new PenaltyData(amount: 100, duration: 0);
    }

    /**
     * @param array<int, PenaltyData> $penalties
     */
    private static function noticeOfDefault(string $date, ?int $duration, array $penalties = []): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
            penalties: $penalties,
        );
    }

    private static function withdrawal(string $date): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
        );
    }

    private static function primaryDecision(string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
        );
    }
}
