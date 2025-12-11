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

class CommitteeHearingScheduledValidatorTest extends TestCase
{
    #[Test]
    public function testItFailsWhenDateIsBeforeLastEvent(): void
    {
        $validator = PetitionEventType::MEETING_SCHEDULED->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 42,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::MEETING_SCHEDULED,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.date_must_be_latest_event', [
                'event' => PetitionEventType::MEETING_SCHEDULED->label(),
            ]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItFailsWhenCommitteeHearingAlreadyScheduled(): void
    {
        $validator = PetitionEventType::MEETING_SCHEDULED->rule();
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
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::MEETING_SCHEDULED,
            date: CalendarDate::create('2025-01-20'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.only_one_event_allowed', [
                'event' => PetitionEventType::MEETING_SCHEDULED->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItPassesWhenDateIsValidEvenWithoutReceiptOfObjection(): void
    {
        $validator = PetitionEventType::MEETING_SCHEDULED->rule();
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
            type: PetitionEventType::MEETING_SCHEDULED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItPassesWhenDateIsAfterLastEventAndNoOtherHearingScheduled(): void
    {
        $validator = PetitionEventType::MEETING_SCHEDULED->rule();
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
            type: PetitionEventType::MEETING_SCHEDULED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItFailsWhenDateIsNotInObjectionOrDecisionPeriod(): void
    {
        $validator = PetitionEventType::MEETING_SCHEDULED->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::PRIMARY_DECISION,
                date: CalendarDate::create('2025-01-01'),
                createdAt: CarbonImmutable::now(),
                duration: 1,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::MEETING_SCHEDULED,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_in_term', [
                'event' => PetitionEventType::MEETING_SCHEDULED->label(),
            ]),
            $result->getErrors()['date'],
        );
    }
}
