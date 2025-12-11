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

class PrimaryDecisionValidatorTest extends TestCase
{
    #[Test]
    public function testItPassesWhenNoPrimaryDecisionExists(): void
    {
        $validator = PetitionEventType::PRIMARY_DECISION->rule();
        $state = new DerivedState();
        $state->addEvents(collect([]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-01'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }

    #[Test]
    public function testItFailsWhenPrimaryDecisionAlreadyExists(): void
    {
        $validator = PetitionEventType::PRIMARY_DECISION->rule();
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
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-15'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertStringContainsString(
            __('term.validation.common.only_one_event_allowed', ['event' => PetitionEventType::PRIMARY_DECISION->label()]),
            $result->getErrors()['general'],
        );
    }

    #[Test]
    public function testItPassesWhenOnlyOtherEventsExist(): void
    {
        $validator = PetitionEventType::PRIMARY_DECISION->rule();
        $state = new DerivedState();
        $state->addEvents(collect([
            new PetitionEventData(
                type: PetitionEventType::RECEIPT_OF_OBJECTION,
                date: CalendarDate::create('2025-01-15'),
                createdAt: CarbonImmutable::now(),
                duration: 30,
            ),
        ]));

        $result = $validator->validate(new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-01-01'),
            createdAt: CarbonImmutable::now(),
            duration: 42,
        ), $state);

        $this->assertTrue($result->isValid());
        $this->assertEmpty($result->getErrors());
    }
}
