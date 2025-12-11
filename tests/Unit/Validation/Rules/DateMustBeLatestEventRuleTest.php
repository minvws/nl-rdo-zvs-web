<?php

declare(strict_types=1);

namespace Tests\Unit\Validation\Rules;

use App\Enums\PetitionEventType;
use App\Services\DerivedState;
use App\Validation\Rules\DateMustBeLatestEventRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\PetitionEventData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DateMustBeLatestEventRuleTest extends TestCase
{
    private DateMustBeLatestEventRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new DateMustBeLatestEventRule();
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
    public function testPassesWhenDateIsAfterLastEvent(): void
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
    public function testFailsWhenDateIsBeforeLastEvent(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $existingEvent = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-12-01'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$existingEvent]));

        $result = $this->rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
        $this->assertArrayHasKey('date', $result->getErrors());
    }

    #[Test]
    public function testPassesWhenDateIsSameAsLastEvent(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-11-01'),
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
    public function testChecksAgainstMostRecentEvent(): void
    {
        $event = new PetitionEventData(
            type: PetitionEventType::HEARING_DATE,
            date: CalendarDate::create('2025-11-15'),
            createdAt: CarbonImmutable::now(),
        );

        $oldEvent = new PetitionEventData(
            type: PetitionEventType::RECEIPT_OF_OBJECTION,
            date: CalendarDate::create('2025-11-01'),
            createdAt: CarbonImmutable::now(),
        );

        $recentEvent = new PetitionEventData(
            type: PetitionEventType::PRIMARY_DECISION,
            date: CalendarDate::create('2025-11-20'),
            createdAt: CarbonImmutable::now(),
        );

        $state = $this->createMock(DerivedState::class);
        $state->method('getEvents')->willReturn(new Collection([$oldEvent, $recentEvent]));

        $result = $this->rule->validate($event, $state);

        $this->assertNotNull($result);
        $this->assertFalse($result->isValid());
    }
}
