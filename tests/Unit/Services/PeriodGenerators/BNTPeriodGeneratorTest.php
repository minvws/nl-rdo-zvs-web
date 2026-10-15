<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\BNTPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function array_keys;
use function collect;

class BNTPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    /**
     * @return array<string, array{PetitionEventData}>
     */
    public static function withoutTermProvider(): array
    {
        return [
            'no appeal decision not timely' => [self::event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 30)],
            'a term of zero days' => [self::appealNotTimely('2025-01-01', term: 0)],
            'no term' => [self::appealNotTimely('2025-01-01', term: null)],
            'a term of zero days with penalties' => [
                self::appealNotTimely('2025-01-01', term: 0, penalties: [new PenaltyData(amount: 100, duration: 2)]),
            ],
            'no term with penalties' => [
                self::appealNotTimely('2025-01-01', term: null, penalties: [new PenaltyData(amount: 100, duration: 2)]),
            ],
        ];
    }

    #[Test]
    #[DataProvider('withoutTermProvider')]
    public function testGeneratesNothingWithoutATerm(PetitionEventData $event): void
    {
        $calendar = new EventCalendar();

        new BNTPeriodGenerator()->generate(collect([$event]), $calendar);

        $this->assertEmpty($calendar->all());
    }

    /**
     * @return array<string, array{
     *     events: list<PetitionEventData>,
     *     budgetDays: list<string>,
     *     firstDays: list<string>,
     *     deadlines: list<string>,
     *     penaltyDays: array<string, int>,
     * }>
     */
    public static function termProvider(): array
    {
        return [
            'a term of a single day' => [
                'events' => [self::appealNotTimely('2025-01-01', term: 1)],
                'budgetDays' => ['2025-01-02'],
                'firstDays' => ['2025-01-02'],
                'deadlines' => ['2025-01-02'],
                'penaltyDays' => [],
            ],
            'a term of several days' => [
                'events' => [self::appealNotTimely('2025-01-01', term: 5)],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-06'),
                'firstDays' => ['2025-01-02'],
                'deadlines' => ['2025-01-06'],
                'penaltyDays' => [],
            ],
            // Unlike the other terms, this term is not moved to the next working day.
            'a term ending on a weekend is not moved' => [
                'events' => [self::appealNotTimely('2025-01-01', term: 3)],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-04'),
                'firstDays' => ['2025-01-02'],
                'deadlines' => ['2025-01-04'],
                'penaltyDays' => [],
            ],
            'penalties start the day after the term' => [
                'events' => [
                    self::appealNotTimely('2025-01-01', term: 2, penalties: [new PenaltyData(amount: 100, duration: 3)]),
                ],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'firstDays' => ['2025-01-02'],
                'deadlines' => ['2025-01-03'],
                'penaltyDays' => ['2025-01-04' => 100, '2025-01-05' => 100, '2025-01-06' => 100],
            ],
            'penalty tiers follow each other' => [
                'events' => [
                    self::appealNotTimely('2025-01-01', term: 1, penalties: [
                        new PenaltyData(amount: 100, duration: 2),
                        new PenaltyData(amount: 50, duration: 2),
                    ]),
                ],
                'budgetDays' => ['2025-01-02'],
                'firstDays' => ['2025-01-02'],
                'deadlines' => ['2025-01-02'],
                'penaltyDays' => ['2025-01-03' => 100, '2025-01-04' => 100, '2025-01-05' => 50, '2025-01-06' => 50],
            ],
            'every appeal decision not timely gets its own term' => [
                'events' => [
                    self::appealNotTimely('2025-01-01', term: 2),
                    self::appealNotTimely('2025-02-01', term: 2, penalties: [new PenaltyData(amount: 25, duration: 1)]),
                ],
                'budgetDays' => ['2025-01-02', '2025-01-03', '2025-02-02', '2025-02-03'],
                'firstDays' => ['2025-01-02', '2025-02-02'],
                'deadlines' => ['2025-01-03', '2025-02-03'],
                'penaltyDays' => ['2025-02-04' => 25],
            ],
        ];
    }

    /**
     * @param list<PetitionEventData> $events
     * @param list<string> $budgetDays
     * @param list<string> $firstDays
     * @param list<string> $deadlines
     * @param array<string, int> $penaltyDays
     */
    #[Test]
    #[DataProvider('termProvider')]
    public function testTerm(array $events, array $budgetDays, array $firstDays, array $deadlines, array $penaltyDays): void
    {
        $calendar = new EventCalendar();

        new BNTPeriodGenerator()->generate(collect($events), $calendar);

        $appealNotTimely = TermType::APPEAL_NOT_TIMELY;
        $this->assertSame($budgetDays, $this->budgetDates($calendar, $appealNotTimely));
        // Penalty days never mark the first or last day of a budget.
        $this->assertSame($firstDays, $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isFirstDayOfBudget));
        $this->assertSame($deadlines, $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isLastDayOfBudget));
        $this->assertSame($deadlines, $this->deadlineDates($calendar, $appealNotTimely));
        $this->assertSame([], $this->atwDates($calendar, $appealNotTimely));
        $this->assertSame($penaltyDays, $calendar
            ->filter(static fn(EventCalendarDay $day): bool => $day->applicableTerm === TermType::PENALTY_PERIOD->value)
            ->mapWithKeys(static fn(EventCalendarDay $day): array => [$day->date->toDateString() => $day->penaltyTodayInEuros])
            ->sortKeys()
            ->all());

        foreach (array_keys($penaltyDays) as $penaltyDay) {
            $day = $calendar->findDay(CalendarDate::create($penaltyDay));
            $this->assertFalse($day->isBudgetDay, $penaltyDay);
            $this->assertSame($appealNotTimely->value, $day->penaltySourceTerm, $penaltyDay);
        }
    }

    #[Test]
    public function testPenaltyDataRejectsZeroDuration(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Penalty duration must be positive');

        new PenaltyData(amount: 100, duration: 0);
    }

    /**
     * @param list<PenaltyData> $penalties
     */
    private static function appealNotTimely(string $date, ?int $term, array $penalties = []): PetitionEventData
    {
        return self::event(PetitionEventType::APPEAL_DECISION_NOT_TIMELY, $date, $term, $penalties);
    }

    /**
     * @param array<int, PenaltyData> $penalties
     */
    private static function event(PetitionEventType $type, string $date, ?int $duration, array $penalties = []): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
            penalties: $penalties,
        );
    }
}
