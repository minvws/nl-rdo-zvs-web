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

class ObjectionReceiptValidatorTest extends TestCase
{
    #[Test]
    public function testItFailsWhenNoPrimaryDecisionExists(): void
    {
        $validator = PetitionEventType::RECEIPT_OF_OBJECTION->rule();
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertSame(
            __('term.validation.common.event_requires_dependency', [
                'event' => PetitionEventType::RECEIPT_OF_OBJECTION->label(),
                'required_event' => PetitionEventType::PRIMARY_DECISION->label(),
            ]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItAllowsDateIsBeforePrimaryDecision(): void
    {
        $validator = PetitionEventType::RECEIPT_OF_OBJECTION->rule();
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
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-10'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testItPassesWhenDateIsAfterPrimaryDecision(): void
    {
        $validator = PetitionEventType::RECEIPT_OF_OBJECTION->rule();
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
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 30,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
}
