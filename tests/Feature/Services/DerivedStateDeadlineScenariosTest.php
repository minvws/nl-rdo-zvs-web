<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\FakeLegalTermDeadlineCalculator;

use function collect;
use function sort;

/**
 * Petitions run through every period generator, checking the deadline that ends up on the petition.
 * Unless a scenario says otherwise, the primary decision is on Wednesday 2025-01-01 and the objection (42 days) is
 * received on Monday 2025-03-03, after the objection period ended on 2025-02-12, so the decision period runs
 * from 03-04 to Monday 2025-04-14.
 * Today is 2025-04-01.
 *
 * Every scenario is checked in both UnspecifiedAdjournmentMode modes. The deadlines are those for departments that
 * freeze their terms, 'continuesTerms' lists the ones that differ for departments that keep their terms running.
 * Without an unspecified adjournment both modes must agree.
 */
class DerivedStateDeadlineScenariosTest extends FeatureTestCase
{
    /**
     * @return array<string, array{
     *     events: list<PetitionEventData>,
     *     holidays: list<string>,
     *     objectionDeadline: string|null,
     *     decisionDeadline: string|null,
     *     deadline: string|null,
     *     continuesTerms?: array{objectionDeadline?: string|null, decisionDeadline?: string|null, deadline?: string|null},
     * }>
     */
    public static function scenarioProvider(): array
    {
        $primaryDecision = self::event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42);
        $objection = self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-03-03', 42);

        return [
            'an objection received within the objection period waits for it to end' => [
                'events' => [$primaryDecision, self::event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-20', 42)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-03-26',
                'deadline' => '2025-03-26',
            ],
            'an objection received after the objection period' => [
                'events' => [$primaryDecision, $objection],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-14',
                'deadline' => '2025-04-14',
            ],
            'a Woo request without an objection period' => [
                'events' => [self::event(PetitionEventType::PETITION_RECEIVED, '2025-01-01', 28)],
                'holidays' => [],
                'objectionDeadline' => null,
                'decisionDeadline' => '2025-01-29',
                'deadline' => '2025-01-29',
            ],
            'a public holiday on the decision deadline' => [
                'events' => [$primaryDecision, $objection],
                'holidays' => ['2025-04-14'],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-15',
                'deadline' => '2025-04-15',
            ],
            'a public holiday on the objection deadline' => [
                'events' => [$primaryDecision, $objection],
                'holidays' => ['2025-02-12'],
                'objectionDeadline' => '2025-02-13',
                'decisionDeadline' => '2025-04-14',
                'deadline' => '2025-04-14',
            ],
            // Statutory 03-04 to 03-09, verdaging 03-10 to 04-20, statutory again from 04-21 for its remaining 36 days
            'a verdaging of six weeks' => [
                'events' => [$primaryDecision, $objection, self::event(PetitionEventType::ADJOURNMENT, '2025-03-10', 42)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-05-26',
                'deadline' => '2025-05-26',
            ],
            'a suspension of two weeks' => [
                'events' => [$primaryDecision, $objection, self::suspension('2025-03-10', 14)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-28',
                'deadline' => '2025-04-28',
            ],
            'a suspension ended early' => [
                'events' => [
                    $primaryDecision,
                    $objection,
                    self::suspension('2025-03-10', 14),
                    self::event(PetitionEventType::SUSPENSION_END, '2025-03-14', null),
                ],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-17',
                'deadline' => '2025-04-17',
            ],
            // Suspended are 03-11 to 03-13 and 03-21; a new suspension can only start once the previous one has ended
            'two suspensions after each other' => [
                'events' => [
                    $primaryDecision,
                    $objection,
                    self::suspension('2025-03-10', 14),
                    self::event(PetitionEventType::SUSPENSION_END, '2025-03-14', null),
                    self::suspension('2025-03-20', 5),
                    self::event(PetitionEventType::SUSPENSION_END, '2025-03-22', null),
                ],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-18',
                'deadline' => '2025-04-18',
            ],
            // Frozen are 03-10 to 03-16. Without a registered term, ending the adjournment does not
            // extend a term that kept running
            'an unspecified adjournment of a week without a registered term' => [
                'events' => [$primaryDecision, $objection, self::adjournment('2025-03-10'), self::adjournmentEnd('2025-03-17')],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-21',
                'deadline' => '2025-04-21',
                'continuesTerms' => ['decisionDeadline' => '2025-04-14', 'deadline' => '2025-04-14'],
            ],
            // The new term of 7 days from 03-17 ends on Monday 03-24, before the original deadline
            'an unspecified adjournment whose registered term ends before the original deadline' => [
                'events' => [$primaryDecision, $objection, self::adjournment('2025-03-10', 7), self::adjournmentEnd('2025-03-17')],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-21',
                'deadline' => '2025-04-21',
                'continuesTerms' => ['decisionDeadline' => '2025-04-14', 'deadline' => '2025-04-14'],
            ],
            // Frozen are 03-10 to 03-31, so the 36 statutory days left run from 04-01 to Tuesday 05-06.
            // A department that keeps its terms running starts a new term of 42 days on 04-01 instead.
            'an unspecified adjournment ended by an event' => [
                'events' => [$primaryDecision, $objection, self::adjournment('2025-03-10', 42, 28), self::adjournmentEnd('2025-04-01')],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-05-06',
                'deadline' => '2025-05-06',
                'continuesTerms' => ['decisionDeadline' => '2025-05-12', 'deadline' => '2025-05-12'],
            ],
            // A withdrawal uses the withdrawal term of 28 days: 04-01 to Monday 04-28
            'an unspecified adjournment ended by a withdrawal' => [
                'events' => [
                    $primaryDecision,
                    $objection,
                    self::adjournment('2025-03-10', 42, 28),
                    self::adjournmentEnd('2025-04-01', AdjournmentEndReason::Withdrawal),
                ],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-05-06',
                'deadline' => '2025-05-06',
                'continuesTerms' => ['decisionDeadline' => '2025-04-28', 'deadline' => '2025-04-28'],
            ],
            // While it is open, frozen terms make today the deadline. The projected decision deadline
            // assumes the freeze ends today. A term that keeps running keeps its original deadline.
            'an open unspecified adjournment' => [
                'events' => [$primaryDecision, $objection, self::adjournment('2025-03-10', 42, 28)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-05-06',
                'deadline' => '2025-04-01',
                'continuesTerms' => ['decisionDeadline' => '2025-04-14', 'deadline' => '2025-04-14'],
            ],
            // Frozen are 01-10 to 01-16 inside the objection period, which moves to Wednesday 02-19.
            // A department that keeps its terms running ends the objection period on 02-12 regardless.
            'an unspecified adjournment during the objection period' => [
                'events' => [$primaryDecision, $objection, self::adjournment('2025-01-10'), self::adjournmentEnd('2025-01-17')],
                'holidays' => [],
                'objectionDeadline' => '2025-02-19',
                'decisionDeadline' => '2025-04-14',
                'deadline' => '2025-04-14',
                'continuesTerms' => ['objectionDeadline' => '2025-02-12'],
            ],
            // The notice of default term of two weeks ends on Sunday 05-04 and moves to Monday
            'a notice of default after the decision deadline' => [
                'events' => [$primaryDecision, $objection, self::event(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, '2025-04-20', 14)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => '2025-04-14',
                'deadline' => '2025-05-05',
            ],
            'a final result ends the petition' => [
                'events' => [$primaryDecision, $objection, self::event(PetitionEventType::FINAL_RESULT, '2025-04-01', null)],
                'holidays' => [],
                'objectionDeadline' => '2025-02-12',
                'decisionDeadline' => null,
                'deadline' => null,
            ],
        ];
    }

    /**
     * @param list<PetitionEventData> $events
     * @param list<string> $holidays
     * @param array{objectionDeadline?: string|null, decisionDeadline?: string|null, deadline?: string|null} $continuesTerms
     */
    #[Test]
    #[DataProvider('scenarioProvider')]
    public function testDeadline(
        array $events,
        array $holidays,
        ?string $objectionDeadline,
        ?string $decisionDeadline,
        ?string $deadline,
        array $continuesTerms = [],
    ): void {
        $this->travelTo(CarbonImmutable::parse('2025-04-01'));
        FakeLegalTermDeadlineCalculator::bind($this->app, $holidays);

        $expectedPerMode = [
            UnspecifiedAdjournmentMode::FREEZES_TERMS->value => [$objectionDeadline, $decisionDeadline, $deadline],
            UnspecifiedAdjournmentMode::CONTINUES_TERMS->value => [
                $continuesTerms['objectionDeadline'] ?? $objectionDeadline,
                $continuesTerms['decisionDeadline'] ?? $decisionDeadline,
                $continuesTerms['deadline'] ?? $deadline,
            ],
        ];

        foreach ($expectedPerMode as $mode => [$expectedObjectionDeadline, $expectedDecisionDeadline, $expectedDeadline]) {
            $derivedState = new DerivedState()
                ->withUnspecifiedAdjournmentMode(UnspecifiedAdjournmentMode::from($mode))
                ->addEvents(collect($events))
                ->buildCalendar();

            $this->assertSame(
                $expectedObjectionDeadline,
                $derivedState->deadlineDateForTerm(TermType::OBJECTION_PERIOD)?->toDateString(),
                $mode . ': objection deadline',
            );
            $this->assertSame(
                $expectedDecisionDeadline,
                $derivedState->deadlineDateForTerm(TermType::DECISION_PERIOD)?->toDateString(),
                $mode . ': decision deadline',
            );
            $this->assertSame($expectedDeadline, $derivedState->deadlineDate()?->toDateString(), $mode . ': deadline');

            // The calendar is kept in date order, whatever order the generators added the days in
            $dates = $derivedState->getCalendar()->keys()->all();
            $sortedDates = $dates;
            sort($sortedDates);
            $this->assertSame($sortedDates, $dates, $mode . ': calendar order');

            // A term never has more than one deadline, also after it was extended
            foreach ([TermType::OBJECTION_PERIOD, TermType::DECISION_PERIOD] as $term) {
                $this->assertLessThanOrEqual(1, $derivedState->getCalendar()->filter(
                    static fn(EventCalendarDay $day): bool => $day->isDeadline && $day->applicableTerm === $term->value,
                )->count(), $mode . ': deadlines of ' . $term->value);
            }
        }
    }

    private static function event(PetitionEventType $type, string $date, ?int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
        );
    }

    private static function suspension(string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
            suspensionType: SuspensionType::SUSPENSION,
        );
    }

    private static function adjournment(string $date, ?int $duration = null, ?int $withdrawalDuration = null): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            duration: $duration,
            withdrawalDuration: $withdrawalDuration,
        );
    }

    private static function adjournmentEnd(string $date, AdjournmentEndReason $reason = AdjournmentEndReason::Event): PetitionEventData
    {
        return new PetitionEventData(
            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::parse($date),
            reasoning: $reason->value,
        );
    }
}
