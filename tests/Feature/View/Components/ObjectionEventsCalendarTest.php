<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Config\DepartmentConfigurationService;
use App\Enums\AdjournmentEndReason;
use App\Enums\DecisionPeriodType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Enums\TermType;
use App\Models\Department;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\EventCalendarDay;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use App\View\Components\ObjectionEventsCalendar;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function in_array;
use function max;
use function preg_match;
use function preg_match_all;

class ObjectionEventsCalendarTest extends FeatureTestCase
{
    public function testFreezingDepartmentPushesTheDecisionTermBackWhileAnAdjournmentIsOpen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $html = $this->renderCalendar(Department::factory()->create(['slug' => 'team-a']));

        $this->assertGreaterThan('2026-04-30', $this->lastDecisionPeriodDate($html));
        $this->assertStringNotContainsString(__('term.is_open_unspecified_adjournment'), $html);
    }

    public function testContinuingDepartmentKeepsTheDecisionTermWhileAnAdjournmentIsOpen(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $html = $this->renderCalendar(Department::factory()->create(['slug' => 'team-c']));

        $this->assertSame('2026-04-30', $this->deadlineDate($html));
        $this->assertStringContainsString(__('term.is_open_unspecified_adjournment'), $html);
    }

    public function testContinuingDepartmentShowsTheWaitingDaysAfterTheDecisionTerm(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $html = $this->renderCalendar(Department::factory()->create(['slug' => 'team-c']));

        $this->assertStringContainsString('calendar-colour--term-decision_period-extended', $html);
        $this->assertStringContainsString(__('term.decision_period_type.extended'), $html);
        $this->assertSame('2026-05-20', $this->lastDecisionPeriodDate($html));
    }

    /**
     * @return array<string, array{DecisionPeriodType, string}>
     */
    public static function nonStatutoryDecisionPeriodTypes(): array
    {
        return [
            'verdaging' => [DecisionPeriodType::ADJOURNMENT, 'Beslistermijn (verdaging)'],
            'afspraak' => [DecisionPeriodType::MEETING_SCHEDULED, 'Beslistermijn (afspraak)'],
            'commissiezitting' => [DecisionPeriodType::COMMITTEE_HEARING, 'Beslistermijn (commissiezitting)'],
            'gebeurtenis' => [DecisionPeriodType::EVENT, 'Beslistermijn (gebeurtenis)'],
            'intrekking' => [DecisionPeriodType::WITHDRAWAL, 'Beslistermijn (intrekking)'],
            'verlengd' => [DecisionPeriodType::EXTENDED, 'Beslistermijn (verlengd)'],
        ];
    }

    #[DataProvider('nonStatutoryDecisionPeriodTypes')]
    public function testNonStatutoryDecisionPeriodNamesItsType(DecisionPeriodType $type, string $expectedLabel): void
    {
        $day = $this->decisionPeriodDay(['decisionPeriodType' => $type]);

        $this->assertSame($expectedLabel, $this->calendarComponent()->termLabel($day));
        $this->assertSame(
            'term-type calendar-colour--term-decision_period calendar-colour--term-decision_period-' . $type->value,
            $this->calendarComponent()->termClass($day),
        );
    }

    public function testStatutoryDecisionPeriodKeepsThePlainLabel(): void
    {
        $day = $this->decisionPeriodDay(['decisionPeriodType' => DecisionPeriodType::STATUTORY]);

        $this->assertSame('Beslistermijn', $this->calendarComponent()->termLabel($day));
        $this->assertSame('term-type calendar-colour--term-decision_period', $this->calendarComponent()->termClass($day));
    }

    public function testSuspensionWinsOverTheTypeOfDecisionPeriod(): void
    {
        $day = $this->decisionPeriodDay([
            'decisionPeriodType' => DecisionPeriodType::ADJOURNMENT,
            'suspensionType' => SuspensionType::SUSPENSION,
        ]);

        $this->assertSame('Beslistermijn (opgeschort)', $this->calendarComponent()->termLabel($day));
        $this->assertSame(
            'term-type calendar-colour--term-decision_period calendar-colour--term-decision_period-suspension',
            $this->calendarComponent()->termClass($day),
        );
    }

    public function testOtherTermsKeepTheirOwnLabel(): void
    {
        $day = EventCalendarDay::new(CalendarDate::create('2026-04-01'), [
            'applicableTerm' => TermType::OBJECTION_PERIOD->value,
        ]);

        $this->assertSame('Bezwaartermijn', $this->calendarComponent()->termLabel($day));
        $this->assertSame('term-type calendar-colour--term-objection_period', $this->calendarComponent()->termClass($day));
    }

    /**
     * @return array<string, array{PetitionEventData, string, string}>
     */
    public static function eventsWithTheirLabelAndClass(): array
    {
        return [
            'plain event' => [
                self::event(PetitionEventType::RECEIPT_OF_OBJECTION),
                'Ontvangst bezwaar',
                'event-type calendar-colour--event-receipt_of_objection',
            ],
            'suspension type' => [
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, suspensionType: SuspensionType::CONSULTATION),
                'Opschortingbrief verzonden (zienswijze)',
                'event-type calendar-colour--event-letter_of_suspension_sent'
                    . ' calendar-colour--event-letter_of_suspension_sent-consultation',
            ],
            'result type' => [
                self::event(PetitionEventType::FINAL_RESULT, resultType: ResultType::FINAL_DECISION_55_REQUEST),
                'Finaal besluit (5.5-verzoek)',
                'event-type calendar-colour--event-final_result calendar-colour--event-final_result-final_decision_55_request',
            ],
            'adjournment ended by an event' => [
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, reasoning: AdjournmentEndReason::Event->value),
                'Gebeurtenis',
                'event-type calendar-colour--event-unspecified_adjournment_end',
            ],
            'adjournment ended by withdrawal' => [
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, reasoning: AdjournmentEndReason::Withdrawal->value),
                'Intrekking ongespecificeerde aanhouding',
                'event-type calendar-colour--event-unspecified_adjournment_end'
                    . ' calendar-colour--event-unspecified_adjournment_end-withdrawal',
            ],
        ];
    }

    #[DataProvider('eventsWithTheirLabelAndClass')]
    public function testEventGetsTheClassOfItsLabel(PetitionEventData $event, string $expectedLabel, string $expectedClass): void
    {
        $this->assertSame($expectedLabel, $this->calendarComponent()->eventLabel($event));
        $this->assertSame($expectedClass, $this->calendarComponent()->eventClass($event));
    }

    /**
     * @return array<string, array{PetitionEventData}>
     */
    public static function everyEvent(): array
    {
        $events = [];

        $typesWithVariants = [
            PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            PetitionEventType::FINAL_RESULT,
            PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
        ];

        foreach (PetitionEventType::cases() as $type) {
            if (in_array($type, $typesWithVariants, true)) {
                continue;
            }
            $events[$type->value] = [self::event($type)];
        }
        foreach (SuspensionType::cases() as $suspensionType) {
            $events['letter_of_suspension_sent.' . $suspensionType->value] = [
                self::event(PetitionEventType::LETTER_OF_SUSPENSION_SENT, suspensionType: $suspensionType),
            ];
        }
        foreach (ResultType::cases() as $resultType) {
            $events['final_result.' . $resultType->value] = [
                self::event(PetitionEventType::FINAL_RESULT, resultType: $resultType),
            ];
        }
        foreach (AdjournmentEndReason::cases() as $reason) {
            $events['unspecified_adjournment_end.' . $reason->value] = [
                self::event(PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, reasoning: $reason->value),
            ];
        }

        return $events;
    }

    #[DataProvider('everyEvent')]
    public function testEveryEventHasALabelAndAColourClass(PetitionEventData $event): void
    {
        $this->assertStringStartsNotWith('term.', $this->calendarComponent()->eventLabel($event));
        $this->assertStringContainsString(
            'calendar-colour--event-' . $event->type->value,
            $this->calendarComponent()->eventClass($event),
        );
    }

    public function testTermTextAddsThePenaltyOfTheDay(): void
    {
        $withoutPenalty = $this->decisionPeriodDay([]);
        $withPenalty = EventCalendarDay::new(CalendarDate::create('2026-04-01'), [
            'applicableTerm' => TermType::PENALTY_PERIOD->value,
            'penaltyTodayInEuros' => 23,
        ]);

        $this->assertSame('Beslistermijn', $this->calendarComponent()->termText($withoutPenalty));
        $this->assertSame('Dwangsom: €23', $this->calendarComponent()->termText($withPenalty));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function daysWithEventsOrParticularities(): array
    {
        return [
            'event' => [
                [
                    'petitionEvents' => [
                        new PetitionEventData(
                            type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                            date: CalendarDate::create('2026-04-01'),
                            createdAt: CarbonImmutable::parse('2026-04-01'),
                        ),
                    ],
                ],
            ],
            'ATW' => [['isATW' => true]],
            'last day' => [['isDeadline' => true]],
            'last day of the statutory term' => [['isOriginalDeadline' => true]],
            'open adjournment' => [['isOpenUnspecifiedAdjournment' => true]],
        ];
    }

    /**
     * @param array<string, mixed> $properties
     */
    #[DataProvider('daysWithEventsOrParticularities')]
    public function testDayWithAnEventOrParticularityStaysVisible(array $properties): void
    {
        $this->assertTrue($this->calendarComponent()->hasEventsOrParticularities($this->decisionPeriodDay($properties)));
    }

    public function testDayThatOnlyContinuesItsTermCanBeFolded(): void
    {
        $day = $this->decisionPeriodDay(['isUnspecifiedAdjournment' => true, 'isBudgetDay' => true]);

        $this->assertFalse($this->calendarComponent()->hasEventsOrParticularities($day));
    }

    public function testDayAfterAnEventCarriesTheSameTermAsTheEventDay(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-20'));

        $html = $this->renderCalendar(Department::factory()->create(['slug' => 'team-c']));

        $this->assertMatchesRegularExpression(
            '/data-term="Beslistermijn"\s+data-has-details="true"[^>]*data-events-calendar-date="2026-04-10"/',
            $html,
        );
        $this->assertMatchesRegularExpression(
            '/data-term="Beslistermijn"\s+data-has-details="false"[^>]*data-events-calendar-date="2026-04-11"/',
            $html,
        );
    }

    public function testDayWithoutATermHasNoLabel(): void
    {
        $day = EventCalendarDay::new(CalendarDate::create('2026-04-01'), []);

        $this->assertSame('', $this->calendarComponent()->termLabel($day));
        $this->assertSame('term-type', $this->calendarComponent()->termClass($day));
    }

    public function testContinuingDepartmentLabelsTheOriginalLastDayOnceTheTermIsExtended(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-13'));

        // 14 days from Tuesday 2026-05-12 end on Monday 2026-05-25.
        $html = $this->renderCalendar(
            Department::factory()->create(['slug' => 'team-c']),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2026-05-12'),
                createdAt: CarbonImmutable::now(),
                reasoning: AdjournmentEndReason::Event->value,
            ),
        );

        $this->assertStringContainsString(__('term.is_original_deadline-decision_period'), $html);
        $this->assertSame('2026-05-25', $this->lastDecisionPeriodDate($html));
        $this->assertStringNotContainsString(__('term.is_open_unspecified_adjournment'), $html);
    }

    /**
     * @param array<string, mixed> $properties
     */
    private function decisionPeriodDay(array $properties): EventCalendarDay
    {
        return EventCalendarDay::new(CalendarDate::create('2026-04-01'), [
            'applicableTerm' => TermType::DECISION_PERIOD->value,
            ...$properties,
        ]);
    }

    private static function event(
        PetitionEventType $type,
        ?SuspensionType $suspensionType = null,
        ?ResultType $resultType = null,
        ?string $reasoning = null,
    ): PetitionEventData {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create('2026-04-01'),
            createdAt: CarbonImmutable::parse('2026-04-01'),
            suspensionType: $suspensionType,
            resultType: $resultType,
            reasoning: $reasoning,
        );
    }

    private function calendarComponent(): ObjectionEventsCalendar
    {
        return new ObjectionEventsCalendar(
            WizardEventCollection::make(),
            Department::factory()->make(['slug' => 'team-c']),
            $this->app->make(DepartmentConfigurationService::class),
        );
    }

    private function renderCalendar(Department $department, PetitionEventData ...$extraEvents): string
    {
        $events = new WizardEventCollection(new Collection([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2026-04-01'),
                createdAt: CarbonImmutable::now(),
                duration: 29,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2026-04-10'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                withdrawalDuration: 28,
            ),
            ...$extraEvents,
        ]));

        return (string) $this->component(ObjectionEventsCalendar::class, [
            'events' => $events,
            'department' => $department,
        ]);
    }

    private function deadlineDate(string $html): string
    {
        preg_match('/<tr\s+class="deadline"[^>]*data-events-calendar-date="([\d-]+)"/', $html, $matches);

        return $matches[1];
    }

    private function lastDecisionPeriodDate(string $html): string
    {
        preg_match_all('/data-applicable-term="decision_period"\s+data-events-calendar-date="([\d-]+)"/', $html, $matches);

        return max($matches[1]);
    }
}
