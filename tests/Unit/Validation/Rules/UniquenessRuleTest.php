<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\Rules\UniquenessRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UniquenessRuleTest extends TestCase
{
    private UniquenessRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new UniquenessRule();
    }

    #[Test]
    public function testPassesWhenNoExistingEvents(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection());

        $result = $this->rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testPassesWhenEventTypeDoesNotExist(): void
    {
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

        $result = $this->rule->validate($event, $state);

        $this->assertNull($result);
    }

    #[Test]
    public function testFailsWhenEventTypeAlreadyExists(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $this->rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('general', $result->getErrors());
    }

    #[Test]
    public function testFailsWhenMultipleEventsExistIncludingSameType(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent1 = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent2 = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-11-10'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent1, $existingEvent2]));

        $result = $this->rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
    }
}
