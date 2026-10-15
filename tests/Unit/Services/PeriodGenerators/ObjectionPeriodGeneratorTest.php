<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\PeriodGenerators\ObjectionPeriodGenerator;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendar;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;
use Tests\Helpers\InspectsEventCalendar;
use Tests\TestCase;

use function collect;

class ObjectionPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    #[Test]
    public function testGeneratesNothingWithoutAPrimaryDecision(): void
    {
        FakeLegalTermDeadlineCalculator::bind($this->app);
        $calendar = new EventCalendar();

        new ObjectionPeriodGenerator()->generate(collect([
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 42),
        ]), $calendar);

        $this->assertEmpty($calendar->all());
    }

    #[Test]
    public function testPrimaryDecisionWithoutATermSpendsNoBudget(): void
    {
        FakeLegalTermDeadlineCalculator::bind($this->app);
        $calendar = new EventCalendar();

        new ObjectionPeriodGenerator()->generate(collect([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', null),
        ]), $calendar);

        $this->assertSame([], $this->budgetDates($calendar, TermType::OBJECTION_PERIOD));
    }

    /**
     * 2025-01-01 is a Wednesday; 01-04/05 and 01-11/12 are weekends.
     *
     * @return array<string, array{
     *     primaryDecision: string,
     *     term: int,
     *     frozenDays: list<string>,
     *     holidays: list<string>,
     *     budgetDays: list<string>,
     *     atwDays: list<string>,
     *     deadline: string,
     * }>
     */
    public static function objectionPeriodProvider(): array
    {
        return [
            'term ending on a working day' => [
                'primaryDecision' => '2025-01-01',
                'term' => 2,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
            ],
            'a term of a single day' => [
                'primaryDecision' => '2025-01-02',
                'term' => 1,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => ['2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
            ],
            'term ending on a Saturday moves to Monday' => [
                'primaryDecision' => '2025-01-01',
                'term' => 3,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-03', '2025-01-04'],
                'atwDays' => ['2025-01-04', '2025-01-05'],
                'deadline' => '2025-01-06',
            ],
            'term ending on a Sunday moves to Monday' => [
                'primaryDecision' => '2025-01-01',
                'term' => 4,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-05'),
                'atwDays' => ['2025-01-05'],
                'deadline' => '2025-01-06',
            ],
            'term ending on a public holiday moves to the next day' => [
                'primaryDecision' => '2025-01-01',
                'term' => 5,
                'frozenDays' => [],
                'holidays' => ['2025-01-06'],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-06'),
                'atwDays' => ['2025-01-06'],
                'deadline' => '2025-01-07',
            ],
            'a public holiday right after the weekend extends the move' => [
                'primaryDecision' => '2025-01-01',
                'term' => 3,
                'frozenDays' => [],
                'holidays' => ['2025-01-06'],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-04'),
                'atwDays' => self::dateRange('2025-01-04', '2025-01-06'),
                'deadline' => '2025-01-07',
            ],
            'a public holiday inside the term does not shift it' => [
                'primaryDecision' => '2025-01-01',
                'term' => 2,
                'frozenDays' => [],
                'holidays' => ['2025-01-02'],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
            ],
            'a term crossing the turn of the year' => [
                'primaryDecision' => '2024-12-29',
                'term' => 3,
                'frozenDays' => [],
                'holidays' => ['2025-01-01'],
                'budgetDays' => ['2024-12-30', '2024-12-31', '2025-01-01'],
                'atwDays' => ['2025-01-01'],
                'deadline' => '2025-01-02',
            ],
            'a term crossing a leap day' => [
                'primaryDecision' => '2024-02-27',
                'term' => 3,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => ['2024-02-28', '2024-02-29', '2024-03-01'],
                'atwDays' => [],
                'deadline' => '2024-03-01',
            ],
            'the default term of six weeks' => [
                'primaryDecision' => '2025-01-01',
                'term' => 42,
                'frozenDays' => [],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-02', '2025-02-12'),
                'atwDays' => [],
                'deadline' => '2025-02-12',
            ],
            'frozen days at the start shift the whole term' => [
                'primaryDecision' => '2025-01-01',
                'term' => 3,
                'frozenDays' => ['2025-01-02', '2025-01-03'],
                'holidays' => [],
                'budgetDays' => self::dateRange('2025-01-04', '2025-01-06'),
                'atwDays' => [],
                'deadline' => '2025-01-06',
            ],
            'a frozen day in the middle shifts the rest of the term' => [
                'primaryDecision' => '2025-01-05',
                'term' => 3,
                'frozenDays' => ['2025-01-07'],
                'holidays' => [],
                'budgetDays' => ['2025-01-06', '2025-01-08', '2025-01-09'],
                'atwDays' => [],
                'deadline' => '2025-01-09',
            ],
            'frozen days pushing the last day onto a weekend' => [
                'primaryDecision' => '2025-01-01',
                'term' => 2,
                'frozenDays' => ['2025-01-03'],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-04'],
                'atwDays' => ['2025-01-04', '2025-01-05'],
                'deadline' => '2025-01-06',
            ],
            'a frozen day outside the term changes nothing' => [
                'primaryDecision' => '2025-01-01',
                'term' => 2,
                'frozenDays' => ['2025-01-10'],
                'holidays' => [],
                'budgetDays' => ['2025-01-02', '2025-01-03'],
                'atwDays' => [],
                'deadline' => '2025-01-03',
            ],
        ];
    }

    /**
     * @param list<string> $frozenDays
     * @param list<string> $holidays
     * @param list<string> $budgetDays
     * @param list<string> $atwDays
     */
    #[Test]
    #[DataProvider('objectionPeriodProvider')]
    public function testObjectionPeriod(
        string $primaryDecision,
        int $term,
        array $frozenDays,
        array $holidays,
        array $budgetDays,
        array $atwDays,
        string $deadline,
    ): void {
        FakeLegalTermDeadlineCalculator::bind($this->app, $holidays);

        $calendar = new EventCalendar();
        foreach ($frozenDays as $frozenDay) {
            $calendar->upsertDay(CalendarDate::create($frozenDay), ['isUnspecifiedAdjournment' => true]);
        }

        new ObjectionPeriodGenerator()->generate(collect([
            $this->event(PetitionEventType::PRIMARY_DECISION, $primaryDecision, $term),
        ]), $calendar);

        $term = TermType::OBJECTION_PERIOD;
        $this->assertSame($budgetDays, $this->budgetDates($calendar, $term));
        $this->assertSame([$budgetDays[0]], $this->firstDayOfBudgetDates($calendar, $term));
        $this->assertSame($atwDays, $this->atwDates($calendar, $term));
        $this->assertSame([$deadline], $this->deadlineDates($calendar, $term));
        $this->assertSame([$deadline], $this->lastDayOfBudgetDates($calendar, $term));

        // Frozen days within the term stay part of it without spending budget.
        foreach ($frozenDays as $frozenDay) {
            $day = $calendar->findDay(CalendarDate::create($frozenDay));
            $this->assertFalse($day->isBudgetDay, $frozenDay);

            if ($frozenDay < $deadline) {
                $this->assertSame(TermType::OBJECTION_PERIOD->value, $day->applicableTerm, $frozenDay);
            }
        }

        // Nothing is written outside the term.
        $this->assertNull($calendar->findDay(CalendarDate::create($primaryDecision)));
        $this->assertNull($calendar->findDay(CalendarDate::create($deadline)->addDay()));
    }

    /**
     * @return array<string, array{UnspecifiedAdjournmentMode, int}>
     */
    public static function suspensionModeProvider(): array
    {
        return [
            'departments that freeze their terms do not count the suspended days here' => [UnspecifiedAdjournmentMode::FREEZES_TERMS, 0],
            'departments that keep their terms running count the suspended days' => [UnspecifiedAdjournmentMode::CONTINUES_TERMS, 2],
        ];
    }

    #[Test]
    #[DataProvider('suspensionModeProvider')]
    public function testSuspensionCostsDaysWithoutPausingTheTerm(
        UnspecifiedAdjournmentMode $mode,
        int $expectedDaysSuspended,
    ): void {
        FakeLegalTermDeadlineCalculator::bind($this->app);

        $calendar = new EventCalendar();
        $calendar->upsertDay(CalendarDate::create('2025-01-03'), ['suspensionType' => SuspensionType::SUSPENSION]);
        $calendar->upsertDay(CalendarDate::create('2025-01-06'), ['suspensionType' => SuspensionType::SPECIFICATION]);
        $calendar->upsertDay(CalendarDate::create('2025-01-20'), ['suspensionType' => SuspensionType::SUSPENSION]);

        new ObjectionPeriodGenerator($mode)->generate(collect([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 5),
        ]), $calendar);

        $this->assertSame($expectedDaysSuspended, $calendar->totalDaysSuspended);
        $this->assertSame(self::dateRange('2025-01-02', '2025-01-06'), $this->budgetDates($calendar, TermType::OBJECTION_PERIOD));
        $this->assertSame(['2025-01-06'], $this->deadlineDates($calendar, TermType::OBJECTION_PERIOD));
        $this->assertSame(
            ['2025-01-03', '2025-01-06', '2025-01-20'],
            $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->suspensionType instanceof SuspensionType),
        );
    }

    private function event(PetitionEventType $type, string $date, ?int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }
}
