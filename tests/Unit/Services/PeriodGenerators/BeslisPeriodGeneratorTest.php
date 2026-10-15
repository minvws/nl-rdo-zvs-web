<?php

declare(strict_types=1);

namespace Tests\Unit\Services\PeriodGenerators;

use App\Enums\DecisionPeriodType;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Services\PeriodGenerators\BeslisPeriodGenerator;
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
use function count;
use function range;
use function sprintf;

class BeslisPeriodGeneratorTest extends TestCase
{
    use InspectsEventCalendar;

    protected function setUp(): void
    {
        parent::setUp();

        FakeLegalTermDeadlineCalculator::bind($this->app);
    }

    /**
     * @return array<string, array{list<PetitionEventData>}>
     */
    public static function withoutDecisionPeriodProvider(): array
    {
        return [
            'no receipt' => [
                [
                    self::event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42),
                ],
            ],
            'only an extending event' => [
                [
                    self::event(PetitionEventType::ADJOURNMENT, '2025-01-01', 42),
                ],
            ],
            'a receipt without a term' => [
                [
                    self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', null),
                ],
            ],
            'a receipt with a term of zero days' => [
                [
                    self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 0),
                    self::event(PetitionEventType::ADJOURNMENT, '2025-01-02', 42),
                ],
            ],
            'a receipt with a negative term' => [
                [
                    self::event(PetitionEventType::PETITION_RECEIVED, '2025-01-01', -1),
                ],
            ],
        ];
    }

    /**
     * @param list<PetitionEventData> $events
     */
    #[Test]
    #[DataProvider('withoutDecisionPeriodProvider')]
    public function testGeneratesNothingWithoutADecisionPeriod(array $events): void
    {
        $this->assertEmpty($this->generateCalendar($events)->all());
    }

    /**
     * @return array<string, array{PetitionEventType}>
     */
    public static function receiptTypeProvider(): array
    {
        return [
            'bezwaar' => [PetitionEventType::RECEIPT_OF_OBJECTION],
            'woo-verzoek' => [PetitionEventType::PETITION_RECEIVED],
            'klacht' => [PetitionEventType::RECEIPT_OF_COMPLAINT],
        ];
    }

    #[Test]
    #[DataProvider('receiptTypeProvider')]
    public function testEveryReceiptStartsTheDecisionPeriodTheDayAfter(PetitionEventType $receiptType): void
    {
        $calendar = $this->generateCalendar([
            self::event($receiptType, '2025-01-01', 2),
        ]);

        $this->assertSame(['2025-01-02', '2025-01-03'], $this->budgetDates($calendar, TermType::DECISION_PERIOD));
        $this->assertSame(['2025-01-03'], $this->deadlineDates($calendar, TermType::DECISION_PERIOD));
        $this->assertCount(2, $calendar->all());
    }

    /**
     * 2025-01-01 is a Wednesday; 01-04/05 and 01-11/12 are weekends.
     *
     * @return array<string, array{
     *     receipt: string,
     *     term: int,
     *     objectionPeriod: list<string>,
     *     suspendedDays: list<string>,
     *     frozenDays: list<string>,
     *     holidays: list<string>,
     *     budgetDays: list<string>,
     *     atwDays: list<string>,
     *     deadline: string,
     * }>
     */
    public static function statutoryTermProvider(): array
    {
        $case = static fn(array $overrides): array => [
            'receipt' => '2025-01-01',
            'term' => 2,
            'objectionPeriod' => [],
            'suspendedDays' => [],
            'frozenDays' => [],
            'holidays' => [],
            'budgetDays' => ['2025-01-02', '2025-01-03'],
            'atwDays' => [],
            'deadline' => '2025-01-03',
            ...$overrides,
        ];

        return [
            'term ending on a working day' => $case([]),
            'a term of a single day' => $case([
                'term' => 1,
                'budgetDays' => ['2025-01-02'],
                'deadline' => '2025-01-02',
            ]),
            'term ending on a Saturday moves to Monday' => $case([
                'term' => 3,
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-04'),
                'atwDays' => ['2025-01-04', '2025-01-05'],
                'deadline' => '2025-01-06',
            ]),
            'term ending on a public holiday moves to the next day' => $case([
                'term' => 5,
                'holidays' => ['2025-01-06'],
                'budgetDays' => self::dateRange('2025-01-02', '2025-01-06'),
                'atwDays' => ['2025-01-06'],
                'deadline' => '2025-01-07',
            ]),
            'christmas days at the turn of the year' => $case([
                'receipt' => '2024-12-20',
                'term' => 5,
                'holidays' => ['2024-12-25', '2024-12-26'],
                'budgetDays' => self::dateRange('2024-12-21', '2024-12-25'),
                'atwDays' => ['2024-12-25', '2024-12-26'],
                'deadline' => '2024-12-27',
            ]),
            'a term crossing a leap day' => $case([
                'receipt' => '2024-02-27',
                'term' => 3,
                'budgetDays' => ['2024-02-28', '2024-02-29', '2024-03-01'],
                'deadline' => '2024-03-01',
            ]),
            'the default term of six weeks' => $case([
                'term' => 42,
                'budgetDays' => self::dateRange('2025-01-02', '2025-02-12'),
                'deadline' => '2025-02-12',
            ]),
            'a receipt after the objection period starts the day after the receipt' => $case([
                'receipt' => '2025-01-07',
                'objectionPeriod' => self::dateRange('2025-01-01', '2025-01-05'),
                'budgetDays' => ['2025-01-08', '2025-01-09'],
                'deadline' => '2025-01-09',
            ]),
            'a receipt during the objection period waits for it to finish' => $case([
                'receipt' => '2025-01-03',
                'objectionPeriod' => self::dateRange('2025-01-01', '2025-01-05'),
                'budgetDays' => ['2025-01-06', '2025-01-07'],
                'deadline' => '2025-01-07',
            ]),
            'a receipt on the last day of the objection period' => $case([
                'receipt' => '2025-01-05',
                'objectionPeriod' => self::dateRange('2025-01-01', '2025-01-05'),
                'budgetDays' => ['2025-01-06', '2025-01-07'],
                'deadline' => '2025-01-07',
            ]),
            'suspended days do not spend budget' => $case([
                'receipt' => '2025-01-05',
                'term' => 3,
                'suspendedDays' => ['2025-01-07', '2025-01-08'],
                'budgetDays' => ['2025-01-06', '2025-01-09', '2025-01-10'],
                'deadline' => '2025-01-10',
            ]),
            'suspended days before the term change nothing' => $case([
                'suspendedDays' => ['2024-12-30', '2024-12-31'],
            ]),
            'frozen days do not spend budget' => $case([
                'receipt' => '2025-01-05',
                'term' => 3,
                'frozenDays' => ['2025-01-07'],
                'budgetDays' => ['2025-01-06', '2025-01-08', '2025-01-09'],
                'deadline' => '2025-01-09',
            ]),
            'suspended and frozen days pushing the last day onto a weekend' => $case([
                'suspendedDays' => ['2025-01-02'],
                'frozenDays' => ['2025-01-03'],
                'budgetDays' => ['2025-01-04', '2025-01-05'],
                'atwDays' => ['2025-01-05'],
                'deadline' => '2025-01-06',
            ]),
        ];
    }

    /**
     * @param list<string> $objectionPeriod
     * @param list<string> $suspendedDays
     * @param list<string> $frozenDays
     * @param list<string> $holidays
     * @param list<string> $budgetDays
     * @param list<string> $atwDays
     */
    #[Test]
    #[DataProvider('statutoryTermProvider')]
    public function testStatutoryTerm(
        string $receipt,
        int $term,
        array $objectionPeriod,
        array $suspendedDays,
        array $frozenDays,
        array $holidays,
        array $budgetDays,
        array $atwDays,
        string $deadline,
    ): void {
        FakeLegalTermDeadlineCalculator::bind($this->app, $holidays);

        $calendar = new EventCalendar();
        foreach ($objectionPeriod as $date) {
            $calendar->upsertDay(CalendarDate::create($date), ['applicableTerm' => TermType::OBJECTION_PERIOD->value]);
        }
        foreach ($suspendedDays as $date) {
            $calendar->upsertDay(CalendarDate::create($date), ['suspensionType' => SuspensionType::SUSPENSION]);
        }
        foreach ($frozenDays as $date) {
            $calendar->upsertDay(CalendarDate::create($date), ['isUnspecifiedAdjournment' => true]);
        }

        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, $receipt, $term),
        ], $calendar);

        $decisionPeriod = TermType::DECISION_PERIOD;
        $this->assertSame($budgetDays, $this->budgetDates($calendar, $decisionPeriod));
        $this->assertSame([$budgetDays[0]], $this->firstDayOfBudgetDates($calendar, $decisionPeriod));
        $this->assertSame($atwDays, $this->atwDates($calendar, $decisionPeriod));
        $this->assertSame([$deadline], $this->deadlineDates($calendar, $decisionPeriod));
        $this->assertSame([$deadline], $this->lastDayOfBudgetDates($calendar, $decisionPeriod));

        // The decision period runs without gaps up to the deadline; days that do not spend budget
        // before the first budget day still belong to it.
        $decisionPeriodDays = $this->datesWhere(
            $calendar,
            static fn(EventCalendarDay $day): bool => $day->applicableTerm === $decisionPeriod->value,
        );
        $this->assertSame(self::dateRange($decisionPeriodDays[0], $deadline), $decisionPeriodDays);
        $this->assertLessThanOrEqual($budgetDays[0], $decisionPeriodDays[0]);
        $this->assertSame(
            count($this->datesWhere(
                $calendar,
                static fn(EventCalendarDay $day): bool => $day->suspensionType instanceof SuspensionType
                    && $day->applicableTerm === $decisionPeriod->value,
            )),
            $calendar->totalDaysSuspended,
        );

        foreach ($objectionPeriod as $date) {
            $this->assertSame(TermType::OBJECTION_PERIOD->value, $calendar->findDay(CalendarDate::create($date))?->applicableTerm);
        }
    }

    /**
     * @return array<string, array{PetitionEventType, DecisionPeriodType}>
     */
    public static function extendingEventTypeProvider(): array
    {
        return [
            'verdaging' => [PetitionEventType::ADJOURNMENT, DecisionPeriodType::ADJOURNMENT],
            'afspraak' => [PetitionEventType::MEETING_SCHEDULED, DecisionPeriodType::MEETING_SCHEDULED],
            'commissiezitting' => [PetitionEventType::COMMITTEE_HEARING_DETERMINED, DecisionPeriodType::COMMITTEE_HEARING],
        ];
    }

    #[Test]
    #[DataProvider('extendingEventTypeProvider')]
    public function testExtendingEventPausesTheStatutoryTerm(
        PetitionEventType $extendingEvent,
        DecisionPeriodType $expectedType,
    ): void {
        // Statutory 01-02, extension 01-03 to 01-08, statutory again 01-09 to Monday 01-13.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 6),
            self::event($extendingEvent, '2025-01-03', 6),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-02' => DecisionPeriodType::STATUTORY,
            '2025-01-03' => $expectedType,
            '2025-01-08' => $expectedType,
            '2025-01-09' => DecisionPeriodType::STATUTORY,
            '2025-01-13' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertSame(['2025-01-02', '2025-01-03'], $this->firstDayOfBudgetDates($calendar, TermType::DECISION_PERIOD));
        $this->assertSame(['2025-01-08', '2025-01-13'], $this->lastDayOfBudgetDates($calendar, TermType::DECISION_PERIOD));
        $this->assertSame(self::dateRange('2025-01-02', '2025-01-13'), $this->budgetDates($calendar, TermType::DECISION_PERIOD));
        $this->assertSame(['2025-01-13'], $this->deadlines($calendar));
        $this->assertSame([], $this->originalDeadlines($calendar));
    }

    #[Test]
    public function testExtendingEventWithoutDurationAddsNoDays(): void
    {
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 5),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-02', null),
        ]);

        $this->assertDecisionPeriodTypes(['2025-01-06' => DecisionPeriodType::STATUTORY], $calendar);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-06'))->isDeadline);
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-07')));
    }

    #[Test]
    public function testExtendingEventWithoutDurationDoesNotStopLaterOnes(): void
    {
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-02', null),
            self::event(PetitionEventType::MEETING_SCHEDULED, '2025-01-03', 2),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-02' => DecisionPeriodType::STATUTORY,
            '2025-01-03' => DecisionPeriodType::MEETING_SCHEDULED,
            '2025-01-04' => DecisionPeriodType::MEETING_SCHEDULED,
            '2025-01-05' => DecisionPeriodType::STATUTORY,
        ], $calendar);
    }

    #[Test]
    public function testVerdagingOnTheLastStatutoryDayPausesItsLastDay(): void
    {
        // Statutory 01-02 to 01-06, verdaging 01-07 to 01-09, the last statutory day is Friday 01-10.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 6),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-07', 3),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-06' => DecisionPeriodType::STATUTORY,
            '2025-01-07' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-09' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-10' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertSame(['2025-01-10'], $this->deadlines($calendar));
    }

    #[Test]
    public function testMeetingScheduledPausesForItsOwnDays(): void
    {
        // Agreed on 01-03 that the term runs to Friday 01-17: the event registers the 14 days in
        // between, 01-03 to 01-16. The statutory term has 5 days left after it, up to Tuesday 01-21.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 6),
            self::event(PetitionEventType::MEETING_SCHEDULED, '2025-01-03', 14),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-03' => DecisionPeriodType::MEETING_SCHEDULED,
            '2025-01-16' => DecisionPeriodType::MEETING_SCHEDULED,
            '2025-01-17' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertSame(['2025-01-21'], $this->deadlines($calendar));
    }

    #[Test]
    public function testOnlyTheLastDayMovesToAWorkingDay(): void
    {
        // The committee hearing ends on Sunday 01-05 and the statutory term continues on 01-06 up
        // to Saturday 01-11, which moves to Monday 01-13.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 7),
            self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, '2025-01-03', 3),
        ]);

        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-05'))->isATW);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-11'))->isATW);
        $this->assertSame(['2025-01-13'], $this->deadlines($calendar));
        $this->assertDecisionPeriodTypes([
            '2025-01-05' => DecisionPeriodType::COMMITTEE_HEARING,
            '2025-01-13' => DecisionPeriodType::STATUTORY,
        ], $calendar);
    }

    #[Test]
    public function testExtendingEventsPauseTheTermInTheOrderTheyHappened(): void
    {
        // Verdaging 01-03 to 01-05, the committee hearing registered during it follows on 01-06 and
        // 01-07, the agreement takes 01-09. The statutory days are 01-02, 01-08 and 01-10 to 01-13.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 6),
            self::event(PetitionEventType::MEETING_SCHEDULED, '2025-01-09', 1),
            self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, '2025-01-04', 2),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-03', 3),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-02' => DecisionPeriodType::STATUTORY,
            '2025-01-03' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-05' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-06' => DecisionPeriodType::COMMITTEE_HEARING,
            '2025-01-07' => DecisionPeriodType::COMMITTEE_HEARING,
            '2025-01-08' => DecisionPeriodType::STATUTORY,
            '2025-01-09' => DecisionPeriodType::MEETING_SCHEDULED,
            '2025-01-10' => DecisionPeriodType::STATUTORY,
            '2025-01-13' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertSame(['2025-01-13'], $this->deadlines($calendar));
    }

    #[Test]
    public function testSuspendedAndFrozenDaysShiftTheVerdagingLikeTheStatutoryTerm(): void
    {
        $calendar = new EventCalendar();
        $calendar->upsertDay(CalendarDate::create('2025-01-04'), ['suspensionType' => SuspensionType::SUSPENSION]);
        $calendar->upsertDay(CalendarDate::create('2025-01-05'), ['suspensionType' => SuspensionType::SUSPENSION]);
        $calendar->upsertDay(CalendarDate::create('2025-01-07'), ['isUnspecifiedAdjournment' => true]);

        // The verdaging days are 01-03 and 01-06, the statutory days 01-02, 01-08 and 01-09.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-03', 2),
        ], $calendar);

        $this->assertSame(['2025-01-09'], $this->deadlines($calendar));
        $this->assertSame(2, $calendar->totalDaysSuspended);
        $this->assertDecisionPeriodTypes([
            '2025-01-04' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-06' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-07' => DecisionPeriodType::STATUTORY,
            '2025-01-08' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-04'))->isBudgetDay);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-07'))->isBudgetDay);
    }

    #[Test]
    public function testVerdagingStartingOnASuspendedDayStartsItsBudgetAfterIt(): void
    {
        $calendar = new EventCalendar();
        $calendar->upsertDay(CalendarDate::create('2025-01-03'), ['suspensionType' => SuspensionType::SUSPENSION]);

        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 2),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-03', 2),
        ], $calendar);

        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-03'))->isFirstDayOfBudget);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-04'))->isFirstDayOfBudget);
        $this->assertSame(['2025-01-06'], $this->deadlines($calendar));
    }

    #[Test]
    public function testExtendingEventInTheObjectionPeriodStartsAfterIt(): void
    {
        $calendar = new EventCalendar();
        foreach (range(1, 5) as $day) {
            $calendar->upsertDay(CalendarDate::create(sprintf('2025-01-%02d', $day)), [
                'applicableTerm' => TermType::OBJECTION_PERIOD->value,
            ]);
        }

        // The committee hearing takes 01-06 and 01-07, the statutory term runs from 01-08 to Friday 01-10.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, '2025-01-04', 2),
        ], $calendar);

        $this->assertSame(TermType::OBJECTION_PERIOD->value, $calendar->findDay(CalendarDate::create('2025-01-05'))->applicableTerm);
        $this->assertDecisionPeriodTypes([
            '2025-01-06' => DecisionPeriodType::COMMITTEE_HEARING,
            '2025-01-07' => DecisionPeriodType::COMMITTEE_HEARING,
            '2025-01-08' => DecisionPeriodType::STATUTORY,
        ], $calendar);
        $this->assertSame(['2025-01-10'], $this->deadlines($calendar));
    }

    #[Test]
    public function testVerdagingAfterTheStatutoryTermSetsTheLastDay(): void
    {
        // Registered before a verdaging had to fall inside the statutory term, which ended on 01-04.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-10', 2),
        ]);

        $this->assertSame(['2025-01-13'], $this->deadlines($calendar));
        $this->assertDecisionPeriodTypes(['2025-01-13' => DecisionPeriodType::ADJOURNMENT], $calendar);
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-07')));
    }

    #[Test]
    public function testVerdagingOnTheMovedLastStatutoryDayKeepsTheDaysBeforeIt(): void
    {
        // The statutory term ends on Saturday 01-04, which moves to Monday 01-06. A verdaging on
        // that Monday takes 01-06 and 01-07; the weekend in between stays part of the statutory term.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-06', 2),
        ]);

        $this->assertDecisionPeriodTypes([
            '2025-01-04' => DecisionPeriodType::STATUTORY,
            '2025-01-05' => DecisionPeriodType::STATUTORY,
            '2025-01-06' => DecisionPeriodType::ADJOURNMENT,
            '2025-01-07' => DecisionPeriodType::ADJOURNMENT,
        ], $calendar);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-04'))->isATW);
        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-05'))->isATW);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-05'))->isBudgetDay);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-06'))->isATW);
        $this->assertSame(['2025-01-07'], $this->deadlines($calendar));
        // The weekend days kept in the statutory term start or end nothing.
        $this->assertSame(['2025-01-02', '2025-01-06'], $this->firstDayOfBudgetDates($calendar, TermType::DECISION_PERIOD));
        $this->assertSame(['2025-01-04', '2025-01-07'], $this->lastDayOfBudgetDates($calendar, TermType::DECISION_PERIOD));
    }

    #[Test]
    public function testVerdagingInsideTheMovedLastStatutoryDaysKeepsItsOwnDays(): void
    {
        // The statutory term ends on Saturday 01-04; the verdaging on Sunday 01-05 takes 01-05 and 01-06.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 3),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-05', 2),
        ]);

        $this->assertTrue($calendar->findDay(CalendarDate::create('2025-01-04'))->isATW);
        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-05'))->isATW);
        $this->assertDecisionPeriodTypes(['2025-01-05' => DecisionPeriodType::ADJOURNMENT], $calendar);
        $this->assertSame(['2025-01-06'], $this->deadlines($calendar));
    }

    #[Test]
    public function testVerdagingAfterAStatutoryTermEndingOnAWorkingDayAddsNoDaysBeforeIt(): void
    {
        // Registered before a verdaging had to fall inside the statutory term, which ended on Friday 01-03.
        $calendar = $this->generateCalendar([
            self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-01', 2),
            self::event(PetitionEventType::ADJOURNMENT, '2025-01-08', 1),
        ]);

        $this->assertFalse($calendar->findDay(CalendarDate::create('2025-01-03'))->isATW);
        $this->assertNull($calendar->findDay(CalendarDate::create('2025-01-04')));
        $this->assertSame(['2025-01-08'], $this->deadlines($calendar));
    }

    /**
     * @param array<int, PetitionEventData> $events
     */
    private function generateCalendar(array $events, ?EventCalendar $calendar = null): EventCalendar
    {
        $calendar ??= new EventCalendar();

        new BeslisPeriodGenerator()->generate(collect($events), $calendar);

        return $calendar;
    }

    private static function event(PetitionEventType $type, string $date, ?int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }

    /**
     * @return list<string>
     */
    private function deadlines(EventCalendar $calendar): array
    {
        return $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isDeadline);
    }

    /**
     * @return list<string>
     */
    private function originalDeadlines(EventCalendar $calendar): array
    {
        return $this->datesWhere($calendar, static fn(EventCalendarDay $day): bool => $day->isOriginalDeadline);
    }

    /**
     * @param array<string, DecisionPeriodType> $expectedTypes
     */
    private function assertDecisionPeriodTypes(array $expectedTypes, EventCalendar $calendar): void
    {
        foreach ($expectedTypes as $date => $expectedType) {
            $day = $calendar->findDay(CalendarDate::create($date));

            $this->assertSame(TermType::DECISION_PERIOD->value, $day?->applicableTerm, $date);
            $this->assertSame($expectedType, $day->decisionPeriodType, $date);
        }
    }
}
