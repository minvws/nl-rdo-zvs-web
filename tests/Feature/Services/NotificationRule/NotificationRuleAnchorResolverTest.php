<?php

declare(strict_types=1);

namespace Tests\Feature\Services\NotificationRule;

use App\Enums\AdjournmentEndReason;
use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Services\NotificationRule\NotificationRuleAnchorResolver;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class NotificationRuleAnchorResolverTest extends FeatureTestCase
{
    private NotificationRuleAnchorResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new NotificationRuleAnchorResolver();
    }

    #[Test]
    public function theDeadlineAnchorIsTheEffectiveDeadline(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => CalendarDate::create('2026-11-01'),
            'deadline_override_at' => CalendarDate::create('2026-11-15'),
        ]);
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame(['2026-11-15'], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function aPetitionWithoutDeadlineHasNoDeadlineAnchor(): void
    {
        $petition = Petition::factory()->create(['deadline_at' => null, 'deadline_override_at' => null]);
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame([], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function aPetitionWithoutTheEventOfADeadlineRuleHasNoDeadlineAnchor(): void
    {
        $petition = Petition::factory()->create(['deadline_at' => CalendarDate::create('2026-11-01')]);
        $this->createEvent($petition, PetitionEventType::PRIMARY_DECISION, '2026-10-01');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame([], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function aDecidedPetitionHasNoDeadlineAnchorEvenWithAnOverride(): void
    {
        $petition = Petition::factory()->create(['deadline_override_at' => CalendarDate::create('2026-11-01')]);
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $this->createEvent($petition, PetitionEventType::FINAL_RESULT, '2026-10-02');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame([], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function anOpenUnspecifiedAdjournmentHasNoDeadlineAnchor(): void
    {
        $petition = Petition::factory()->create(['deadline_at' => CalendarDate::create('2026-11-01')]);
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $this->createEvent($petition, PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-10-05');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame([], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function anEndedUnspecifiedAdjournmentKeepsTheDeadlineAnchor(): void
    {
        $petition = Petition::factory()->create(['deadline_at' => CalendarDate::create('2026-11-01')]);
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $this->createEvent($petition, PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-10-05');
        $this->createEvent($petition, PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, '2026-10-08');
        $rule = NotificationRule::factory()->forPetitionDeadline()->create();

        $this->assertSame(['2026-11-01'], $this->anchorDates($rule, $petition));
    }

    #[Test]
    public function theEventDateAnchorsAreTheDatesOfEveryEventOfTheType(): void
    {
        $petition = Petition::factory()->create();
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01');
        $this->createEvent($petition, PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-20');
        $this->createEvent($petition, PetitionEventType::PRIMARY_DECISION, '2026-09-01');
        $rule = NotificationRule::factory()->create([
            'anchor' => NotificationRuleAnchor::EVENT_DATE,
            'event_type' => PetitionEventType::ACTUAL_DISCLOSURE,
        ]);

        $this->assertSame(['2026-10-01', '2026-10-20'], $this->anchorDates($rule, $petition));
    }

    /**
     * @param list<array{PetitionEventType, string, int|null}> $events
     * @param list<string> $expectedDates
     */
    #[Test]
    #[DataProvider('endDateProvider')]
    public function theEventEndDateAnchors(PetitionEventType $ruleEventType, array $events, array $expectedDates): void
    {
        $petition = Petition::factory()->create();
        foreach ($events as [$type, $date, $duration]) {
            $this->createEvent($petition, $type, $date, $duration);
        }
        $rule = NotificationRule::factory()->create([
            'anchor' => NotificationRuleAnchor::EVENT_END_DATE,
            'event_type' => $ruleEventType,
        ]);

        $this->assertSame($expectedDates, $this->anchorDates($rule, $petition));
    }

    /**
     * @return array<string, array{PetitionEventType, list<array{PetitionEventType, string, int|null}>, list<string>}>
     */
    public static function endDateProvider(): array
    {
        return [
            'a meeting ends after its number of days' => [
                PetitionEventType::MEETING_SCHEDULED,
                [[PetitionEventType::MEETING_SCHEDULED, '2026-10-01', 14]],
                ['2026-10-15'],
            ],
            'a meeting without a number of days has no end' => [
                PetitionEventType::MEETING_SCHEDULED,
                [[PetitionEventType::MEETING_SCHEDULED, '2026-10-01', null]],
                [],
            ],
            'a "BNT" ends after its number of days' => [
                PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                [[PetitionEventType::APPEAL_DECISION_NOT_TIMELY, '2026-10-01', 7]],
                ['2026-10-08'],
            ],
            'a suspension ends on its end event' => [
                PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                [
                    [PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2026-10-01', 14],
                    [PetitionEventType::SUSPENSION_END, '2026-10-06', null],
                ],
                ['2026-10-06'],
            ],
            'a suspension without end event ends after its number of days' => [
                PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                [[PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2026-10-01', 14]],
                ['2026-10-15'],
            ],
            'a suspension without end event or number of days has no end' => [
                PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                [[PetitionEventType::LETTER_OF_SUSPENSION_SENT, '2026-10-01', null]],
                [],
            ],
            'an unspecified adjournment ends on its end event' => [
                PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                [
                    [PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-10-01', null],
                    [PetitionEventType::UNSPECIFIED_ADJOURNMENT_END, '2026-10-09', null],
                    [PetitionEventType::UNSPECIFIED_ADJOURNMENT, '2026-10-12', null],
                ],
                ['2026-10-09'],
            ],
            'an event type without an end has no end date' => [
                PetitionEventType::ACTUAL_DISCLOSURE,
                [[PetitionEventType::ACTUAL_DISCLOSURE, '2026-10-01', 14]],
                [],
            ],
        ];
    }

    #[Test]
    public function onlyEventTypesWithAnEndHaveAnEndDate(): void
    {
        $this->assertTrue(NotificationRuleAnchorResolver::hasEndDate(PetitionEventType::MEETING_SCHEDULED));
        $this->assertTrue(NotificationRuleAnchorResolver::hasEndDate(PetitionEventType::UNSPECIFIED_ADJOURNMENT));
        $this->assertFalse(NotificationRuleAnchorResolver::hasEndDate(PetitionEventType::ACTUAL_DISCLOSURE));
    }

    /**
     * @return list<string>
     */
    private function anchorDates(NotificationRule $rule, Petition $petition): array
    {
        return $this->resolver->anchorDates($rule, $petition->refresh())
            ->map(static fn(CalendarDate $date): string => $date->toDateString())
            ->sort()
            ->values()
            ->all();
    }

    private function createEvent(Petition $petition, PetitionEventType $type, string $date, ?int $duration = null): void
    {
        PetitionEvent::factory()->for($petition)->withType($type)->create([
            'date' => CalendarDate::create($date),
            'duration' => $duration,
            'reasoning' => $type->requiresReasoning() ? AdjournmentEndReason::Event->value : null,
        ]);
    }
}
