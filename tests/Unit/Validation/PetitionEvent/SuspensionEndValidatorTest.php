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

use function __;
use function collect;
use function implode;

class SuspensionEndValidatorTest extends TestCase
{
    #[Test]
    public function testItFailsWhenNoEventsExist(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-02-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
    }

    #[Test]
    public function testItFailsWhenNoSuspensionDaysExistAfterLastEvent(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
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
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-02-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.last_event_must_be_one_of', [
                'events' => implode(', ', [
                    PetitionEventType::LETTER_OF_SUSPENSION_SENT->label(),
                    PetitionEventType::MEETING_SCHEDULED->label(),
                ]),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItFailsWhenDateIsNotInActiveSuspensionPeriod(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 5,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-01-09'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        // DateMustBeLatestEventRule will fail here because 2025-01-09 < 2025-01-10
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_latest_event', ['event' => PetitionEventType::SUSPENSION_END->label()]),
            $result->getErrors()['date'],
        );
    }

    #[Test]
    public function testItPassesWhenSuspensionDaysExistAfterLastEventAndDateIsValid(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItPassesWhenSuspensionDaysExistAfterLastEventEvenIfLastEventIsNotSuspension(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::LETTER_OF_SUSPENSION_SENT,
                date: CalendarDate::create('2025-01-10'),
                createdAt: CarbonImmutable::now(),
                duration: 20,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-01-20'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItAcceptsCommitteeHearingScheduledAsValidLastEventType(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
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
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
                suspensionType: SuspensionType::SPECIFIED_ADJOURNMENT,
            ),
            new PetitionEventData(
                type: PetitionEventType::MEETING_SCHEDULED,
                date: CalendarDate::create('2025-01-20'),
                createdAt: CarbonImmutable::now(),
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-01-22'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItRejectsEndSuspensionWhenNotSuspended(): void
    {
        $validator = PetitionEventType::SUSPENSION_END->rule();
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
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 10,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::SUSPENSION_END,
            date: CalendarDate::create('2025-02-25'),
            createdAt: CarbonImmutable::now(),
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.date_must_be_in_suspension'),
            $result->getErrors()['date'],
        );
    }
}
