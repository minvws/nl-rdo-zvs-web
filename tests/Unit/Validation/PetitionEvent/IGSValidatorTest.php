<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
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
        $validator = PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->rule();
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
                'required_events' => PetitionEventType::RECEIPT_OF_OBJECTION->label() . ' of ' . PetitionEventType::PETITION_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
    {
        $validator = PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->rule();
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

        $result = $validator->validate(new PetitionEventData(
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
    public function testItFailsWhenDateIsWithinObjectionOrDecisionPeriod(): void
    {
        $validator = PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->rule();
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
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.date_not_allowed_in_term', [
                'event' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->label(),
                'term' => __('term.term_type.objection_period'),
            ]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItPassesWhenReceiptOfObjectionExistsAndDateIsValid(): void
    {
        $validator = PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->rule();
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
            type: PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED,
            date: CalendarDate::create('2025-03-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
}
