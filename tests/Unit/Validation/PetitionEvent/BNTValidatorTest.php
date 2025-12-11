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

class BNTValidatorTest extends TestCase
{
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
    public function testItFailsWhenDateIsWithinRestrictedTerms(): void
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
                duration: 50,
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

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
        $this->assertStringContainsString(
            'is niet toegestaan tijdens',
            $result->getErrors()['date'],
        );
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
}
