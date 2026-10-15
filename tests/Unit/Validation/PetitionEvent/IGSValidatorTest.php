<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\PetitionEvent\PetitionEventRules;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

use function __;
use function collect;

class IGSValidatorTest extends TestCase
{
    #[Test]
    public function testItFailsWhenNoReceiptOfObjectionExists(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-02-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency_any', [
                'event' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->label(),
                'required_events' => PetitionEventType::RECEIPT_OF_OBJECTION->label()
                    . ' ' . __('general.or') . ' '
                    . PetitionEventType::PETITION_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
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
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_must_be_latest_event', [
                'event' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->label(),
            ]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItPassesWhenDateIsWithinObjectionOrDecisionPeriod(): void
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
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItPassesWhenReceiptOfObjectionExistsAndDateIsValid(): void
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
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-03-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testWithdrawnValidatorPassesWhenLastEventIsReceived(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
                date: CalendarDate::create('2025-03-01'),
                createdAt: CarbonImmutable::parse('2025-03-01'),
                duration: 14,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
            date: CalendarDate::create('2025-03-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testWithdrawnValidatorFailsWhenNoEventsExist(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
            date: CalendarDate::create('2025-03-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
    }

    #[Test]
    public function testWithdrawnValidatorFailsWhenLastEventIsNotReceived(): void
    {
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::parse('2025-01-15'),
                duration: 42,
            ),
        ]));

        $result = PetitionEventRules::validate(PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN, new PetitionEventData(
            type: PetitionEventType::NOTICE_OF_DEFAULT_WITHDRAWN,
            date: CalendarDate::create('2025-03-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
    }
}
