<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PenaltyData;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

class BNTValidatorTest extends TestCase
{
    #[Test]
    public function testReceiptAppealNotTimelyPassesWhenReceiptOfObjectionExists(): void
    {
        $validator = PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testReceiptAppealNotTimelyFailsWhenNoStartEventExists(): void
    {
        $validator = PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency_any', [
                'event' => PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->label(),
                'required_events' => PetitionEventType::RECEIPT_OF_OBJECTION->label() . ' of ' . PetitionEventType::PETITION_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItPassesWhenReceiptAppealNotTimelyExistsAndDateIsValid(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-16'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItFailsWhenNoReceiptAppealNotTimelyExists(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-15'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency', [
                'event' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY->label(),
                'required_event' => PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-10'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_must_be_latest_event', [
                'event' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY->label(),
            ]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItPassesWhenDateIsWithinPenaltyPeriod(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 6,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-14'),
                createdAt: CarbonImmutable::now(),
                duration: 0,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-28'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItPassesWhenReceiptAppealNotTimelyExistsAndDateIsValidAgain(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-04-01'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testAppealDecisionNotTimelyFailsWhenNoOpenReceiptAppealNotTimelyExists(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = $this->createStateWithBntPenaltyPeriod();

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-10'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_open_dependency', [
                'event' => PetitionEventType::APPEAL_DECISION_NOT_TIMELY->label(),
                'required_event' => PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testReceiptAppealNotTimelyPassesWhenDateOverlapsPenaltyPeriod(): void
    {
        $validator = PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY->rule();
        $state = $this->createStateWithBntPenaltyPeriod();

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
            date: CalendarDate::create('2025-02-08'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testAppealDecisionNotTimelyPassesWhenDateOverlapsPenaltyPeriod(): void
    {
        $validator = PetitionEventType::APPEAL_DECISION_NOT_TIMELY->rule();
        $state = $this->createStateWithBntPenaltyPeriod();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-08'),
                createdAt: CarbonImmutable::now(),
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
            date: CalendarDate::create('2025-02-09'),
            createdAt: CarbonImmutable::now(),
            duration: 20,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    private function createStateWithBntPenaltyPeriod(): DerivedState
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 14,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_APPEAL_NOT_TIMELY,
                date: CalendarDate::create('2025-02-02'),
                createdAt: CarbonImmutable::now(),
            ),
            new PetitionEventData(
                type: PetitionEventType::APPEAL_DECISION_NOT_TIMELY,
                date: CalendarDate::create('2025-02-03'),
                createdAt: CarbonImmutable::now(),
                duration: 2,
                penalties: [
                    new PenaltyData(amount: 100, duration: 5),
                ],
            ),
        ]));

        return $state;
    }
}
