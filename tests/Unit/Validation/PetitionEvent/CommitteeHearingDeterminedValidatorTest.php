<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\AdjournmentEndReason;
use App\Enums\PetitionEventType;
use App\Enums\UnspecifiedAdjournmentMode;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;
use function lcfirst;

class CommitteeHearingDeterminedValidatorTest extends TestCase
{
    #[Test]
    public function testItPassesWhenDateIsAfterLastEventAndInTerm(): void
    {
        $result = $this->validateOn('2025-01-15', $this->state([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42),
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-10', 30),
        ]));

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
    {
        $result = $this->validateOn('2025-01-10', $this->state([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-15', 42),
        ]));

        $this->assertSame(
            [
                'date' => __('term.validation.common.date_must_be_latest_event', [
                    'event' => PetitionEventType::COMMITTEE_HEARING_DETERMINED->label(),
                ]),
            ],
            $result->getErrors(),
        );
    }

    #[Test]
    public function testItFailsWhenCommitteeHearingIsAlreadyDetermined(): void
    {
        $result = $this->validateOn('2025-01-20', $this->state([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 42),
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2025-01-10', 30),
            $this->event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, '2025-01-15', 42),
        ]));

        $this->assertSame(
            [
                'general' => __('term.validation.common.only_one_event_allowed', [
                    'event' => PetitionEventType::COMMITTEE_HEARING_DETERMINED->label(),
                ]),
            ],
            $result->getErrors(),
        );
    }

    #[Test]
    public function testItFailsWhenDateIsNotInObjectionOrDecisionPeriod(): void
    {
        $result = $this->validateOn('2025-01-15', $this->state([
            $this->event(PetitionEventType::PRIMARY_DECISION, '2025-01-01', 1),
        ]));

        $this->assertSame(
            [
                'date' => __('term.validation.common.date_must_be_in_term', [
                    'event' => PetitionEventType::COMMITTEE_HEARING_DETERMINED->label(),
                    'term' => lcfirst(__('term.objection_period.default'))
                    . ' ' . __('general.or') . ' '
                    . lcfirst(__('term.decision_period.default')),
                ]),
            ],
            $result->getErrors(),
        );
    }

    #[Test]
    public function testItFailsWhenDateIsInDecisionPeriodButAfterStatutoryTerm(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-05-10'));

        // The statutory term ends on 2026-04-30; ending the unspecified adjournment extends the decision period past it.
        $state = $this->state([
            $this->event(PetitionEventType::RECEIPT_OF_OBJECTION, '2026-04-01', 29),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                date: CalendarDate::create('2026-04-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
                withdrawalDuration: 28,
            ),
            new PetitionEventData(
                type: PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                date: CalendarDate::create('2026-04-21'),
                createdAt: CarbonImmutable::now(),
                reasoning: AdjournmentEndReason::Event->value,
            ),
        ]);

        $this->assertTrue($this->validateOn('2026-04-30', $state)->isValid());
        $this->assertSame(
            [
                'date' => __('term.validation.common.date_after_statutory_term', [
                    'event' => PetitionEventType::COMMITTEE_HEARING_DETERMINED->label(),
                ]),
            ],
            $this->validateOn('2026-05-01', $state)->getErrors(),
        );
    }

    /**
     * @param list<PetitionEventData> $events
     */
    private function state(array $events): DerivedState
    {
        return new DerivedState()
            ->withUnspecifiedAdjournmentMode(UnspecifiedAdjournmentMode::CONTINUES_TERMS)
            ->addEvents(collect($events));
    }

    private function validateOn(string $date, DerivedState $state): ValidationResult
    {
        return PetitionEventRules::validate(
            PetitionEventType::COMMITTEE_HEARING_DETERMINED,
            $this->event(PetitionEventType::COMMITTEE_HEARING_DETERMINED, $date, 42),
            $state,
        );
    }

    private function event(PetitionEventType $type, string $date, int $duration): PetitionEventData
    {
        return new PetitionEventData(
            type: $type,
            date: CalendarDate::create($date),
            createdAt: CarbonImmutable::now(),
            duration: $duration,
        );
    }
}
