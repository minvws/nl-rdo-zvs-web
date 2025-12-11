<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\PetitionEvent;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Services\ValidationResult;
use App\Validation\PetitionEvent\ComposableValidator;
use App\Validation\Rules\ValidationRuleInterface;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ComposableValidatorTest extends TestCase
{
    #[Test]
    public function testPassesWhenNoRules(): void
    {
        $validator = new ComposableValidator([]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $validator->validate($event, $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testPassesWhenAllRulesPass(): void
    {
        $rule1 = $this->createMock(ValidationRuleInterface::class);
        $rule1->method('validate')->willReturn(null);

        $rule2 = $this->createMock(ValidationRuleInterface::class);
        $rule2->method('validate')->willReturn(null);

        $validator = new ComposableValidator([$rule1, $rule2]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $validator->validate($event, $state);

        $this->assertTrue($result->isValid());
    }

    #[Test]
    public function testFailsWhenFirstRuleFails(): void
    {
        $rule1 = $this->createMock(ValidationRuleInterface::class);
        $rule1->method('validate')->willReturn(
            new ValidationResult(['error' => 'First rule failed']),
        );

        $rule2 = $this->createMock(ValidationRuleInterface::class);
        $rule2->expects($this->never())->method('validate');

        $validator = new ComposableValidator([$rule1, $rule2]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('error', $result->getErrors());
    }

    #[Test]
    public function testFailsWhenSecondRuleFails(): void
    {
        $rule1 = $this->createMock(ValidationRuleInterface::class);
        $rule1->method('validate')->willReturn(null);

        $rule2 = $this->createMock(ValidationRuleInterface::class);
        $rule2->method('validate')->willReturn(
            new ValidationResult(['error' => 'Second rule failed']),
        );

        $validator = new ComposableValidator([$rule1, $rule2]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('error', $result->getErrors());
    }

    #[Test]
    public function testStopsOnFirstFailure(): void
    {
        $rule1 = $this->createMock(ValidationRuleInterface::class);
        $rule1->method('validate')->willReturn(
            new ValidationResult(['error' => 'Rule 1 failed']),
        );

        $rule2 = $this->createMock(ValidationRuleInterface::class);
        $rule2->expects($this->never())->method('validate');

        $rule3 = $this->createMock(ValidationRuleInterface::class);
        $rule3->expects($this->never())->method('validate');

        $validator = new ComposableValidator([$rule1, $rule2, $rule3]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $validator->validate($event, $state);

        $this->assertFalse($result->isValid());
    }

    #[Test]
    public function testPassesEventAndStateToEachRule(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $rule = $this->createMock(ValidationRuleInterface::class);
        $rule->expects($this->once())
            ->method('validate')
            ->with($event, $state)
            ->willReturn(null);

        $validator = new ComposableValidator([$rule]);

        $validator->validate($event, $state);
    }
}
