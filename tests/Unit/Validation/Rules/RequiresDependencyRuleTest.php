<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\Rules\RequiresDependencyRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RequiresDependencyRuleTest extends TestCase
{
    #[Test]
    public function testPassesWhenDependencyExists(): void
    {
        $rule = new RequiresDependencyRule(PetitionEventType::RECEIPT_OF_OBJECTION);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWhenDependencyDoesNotExist(): void
    {
        $rule = new RequiresDependencyRule(PetitionEventType::RECEIPT_OF_OBJECTION);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
    }

    #[Test]
    public function testPassesWhenDependencyExistsAmongMultipleEvents(): void
    {
        $rule = new RequiresDependencyRule(PetitionEventType::RECEIPT_OF_OBJECTION);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent1 = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-10-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent2 = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent1, $existingEvent2]));

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWhenOnlyOtherEventTypesExist(): void
    {
        $rule = new RequiresDependencyRule(PetitionEventType::RECEIPT_OF_OBJECTION);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent1 = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-10-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent2 = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent1, $existingEvent2]));

        $result = $rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function testPassesWithArrayOfDependenciesWhenFirstExists(): void
    {
        $rule = new RequiresDependencyRule([
            PetitionEventType::RECEIPT_OF_OBJECTION,
            PetitionEventType::PETITION_RECEIVED,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testPassesWithArrayOfDependenciesWhenSecondExists(): void
    {
        $rule = new RequiresDependencyRule([
            PetitionEventType::RECEIPT_OF_OBJECTION,
            PetitionEventType::PETITION_RECEIVED,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::PETITION_RECEIVED,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWhenNoneOfTheRequiredDependenciesExist(): void
    {
        $rule = new RequiresDependencyRule([
            PetitionEventType::RECEIPT_OF_OBJECTION,
            PetitionEventType::PETITION_RECEIVED,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::FINAL_RESULT,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
        $this->assertStringContainsString('of', $result->getErrors()['general']);
    }

    #[Test]
    public function testBackwardCompatibilityWithSingleDependency(): void
    {
        $rule = new RequiresDependencyRule(PetitionEventType::PRIMARY_DECISION);

        $event = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }
}
