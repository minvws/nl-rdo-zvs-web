<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\Rules\MustBeInTermTypeRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MustBeInTermTypeRuleTest extends TestCase
{
    #[Test]
    public function testPassesWhenEventTypeIsAllowed(): void
    {
        $rule = new MustBeInTermTypeRule([
            PetitionEventType::HEARING_DATE,
            PetitionEventType::FINAL_RESULT,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWhenEventTypeIsNotAllowed(): void
    {
        $rule = new MustBeInTermTypeRule([
            PetitionEventType::HEARING_DATE,
            PetitionEventType::FINAL_RESULT,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('type', $result->getErrors());
    }

    #[Test]
    public function testPassesWithSingleAllowedType(): void
    {
        $rule = new MustBeInTermTypeRule([PetitionEventType::PRIMARY_DECISION]);

        $event = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWithEmptyAllowedTypes(): void
    {
        $rule = new MustBeInTermTypeRule([]);

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
    }

    #[Test]
    public function testPassesWhenEventTypeIsInLargeAllowedList(): void
    {
        $rule = new MustBeInTermTypeRule([
            PetitionEventType::PRIMARY_DECISION,
            PetitionEventType::RECEIPT_OF_OBJECTION,
            PetitionEventType::HEARING_DATE,
            PetitionEventType::FINAL_RESULT,
        ]);

        $event = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $rule->validate($event, $state);

        $this->assertNull($result);
    }
}
