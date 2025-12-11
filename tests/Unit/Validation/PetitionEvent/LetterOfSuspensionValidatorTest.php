<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Enums\SuspensionType;
use App\Services\DerivedState;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Throwable;

use function __;
use function collect;

class LetterOfSuspensionValidatorTest extends TestCase
{
    #[Test]
    public function testItFailsWhenNoReceiptOfObjectionExists(): void
    {
        $validator = PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule();
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
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 14,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency_any', [
                'event' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(),
                'required_events' => PetitionEventType::RECEIPT_OF_OBJECTION->label() . ' of ' . PetitionEventType::PETITION_RECEIVED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
    {
        $validator = PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule();
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
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            duration: 14,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_must_be_latest_event', [
                'event' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(),
            ]),
            $result->getErrors()['date'],
        );
    }

    /**
     * @throws Throwable
     */
    #[Test]
    public function testItFailsWhenDateIsInActiveSuspensionPeriod(): void
    {
        $validator = PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule();
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
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-02-01'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-02-05'),
            createdAt: CarbonImmutable::now(),
            duration: 14,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_already_in_suspension'),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItPassesWhenReceiptOfObjectionExistsAndDateIsValid(): void
    {
        $validator = PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule();
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
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-01-20'),
            createdAt: CarbonImmutable::now(),
            duration: 14,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItFailsWhenDateIsNotInObjectionOrDecisionPeriod(): void
    {
        $validator = PetitionEventType::LETTER_OF_SUSPENSION_SENT->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ),
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            date: CalendarDate::create('2025-02-01'),
            createdAt: CarbonImmutable::now(),
            duration: 14,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_in_term', [
                'event' => PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(),
            ]),
            $result->getErrors()['date'],
        );
    }
}
