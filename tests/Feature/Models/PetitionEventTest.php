<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\PetitionEventType;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\ValueObjects\CalendarDate;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function now;

final class PetitionEventTest extends FeatureTestCase
{
    #[Test]
    public function testPetitionEventCanBeCreated(): void
    {
        $petition = Petition::factory()->create();

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'date' => now()->toDateString(),
            'duration' => 42,
        ]);

        $this->assertInstanceOf(PetitionEvent::class, $event);
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION, $event->type);
        $this->assertEquals(42, $event->duration);
    }

    #[Test]
    public function testPetitionEventBelongsToPetition(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()->for($petition)->create();

        $this->assertInstanceOf(Petition::class, $event->petition);
        $this->assertEquals($petition->id, $event->petition_id);
    }

    #[Test]
    public function testPetitionEventCastDateAttribute(): void
    {
        $petition = Petition::factory()->create();
        $dateString = '2025-01-15';

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'date' => $dateString,
            'duration' => 30,
        ]);

        $event->refresh();

        $this->assertTrue($event->date instanceof CalendarDate);
        $this->assertEquals($dateString, $event->date->toDateString());
    }

    #[Test]
    public function testPetitionEventCastPenaltiesAttribute(): void
    {
        $petition = Petition::factory()->create();
        $penalties = [
            ['duration' => 10, 'amount' => 500],
            ['duration' => 20, 'amount' => 1000],
        ];

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::NOTICE_OF_DEFAULT_RECEIVED->value,
            'date' => now()->toDateString(),
            'duration' => 30,
            'penalties' => $penalties,
        ]);

        $event->refresh();

        $this->assertIsArray($event->penalties);
        $this->assertCount(2, $event->penalties);
        $this->assertEquals(10, $event->penalties[0]['duration']);
        $this->assertEquals(500, $event->penalties[0]['amount']);
    }

    #[Test]
    public function testPetitionEventPenaltiesCanBeNull(): void
    {
        $petition = Petition::factory()->create();

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'date' => now()->toDateString(),
            'duration' => 30,
            'penalties' => null,
        ]);

        $event->refresh();

        $this->assertNull($event->penalties);
    }

    #[Test]
    public function testPetitionEventCanHaveEmptyDuration(): void
    {
        $petition = Petition::factory()->create();

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::HEARING_DATE->value,
            'date' => now()->toDateString(),
            'duration' => null,
        ]);

        $event->refresh();

        $this->assertNull($event->duration);
    }

    #[Test]
    public function testPetitionEventCastsPetitionIdAttribute(): void
    {
        $petition = Petition::factory()->create();

        $event = PetitionEvent::create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'date' => now()->toDateString(),
            'duration' => 30,
        ]);

        $event->refresh();

        // Petition ID is cast to a UUID object
        $this->assertEquals((string) $petition->id, (string) $event->petition_id);
    }

    #[Test]
    public function testPetitionEventUsingFactory(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()->for($petition)->create();

        $this->assertInstanceOf(PetitionEvent::class, $event);
        $this->assertEquals($petition->id, $event->petition_id);
        $this->assertNotNull($event->type);
        $this->assertNotNull($event->date);
        $this->assertIsInt($event->duration);
    }

    #[Test]
    public function testPetitionEventFactoryWithPenalties(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()->for($petition)->withPenalties()->create();

        $this->assertIsArray($event->penalties);
        $this->assertCount(1, $event->penalties);
        $this->assertArrayHasKey('duration', $event->penalties[0]);
        $this->assertArrayHasKey('amount', $event->penalties[0]);
    }

    #[Test]
    public function testPetitionEventFactoryWithSpecificType(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()
            ->for($petition)
            ->withType(PetitionEventType::RECEIPT_OF_OBJECTION)
            ->create();

        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $event->type);
    }

    #[Test]
    public function testPetitionEventTableHasRequiredColumns(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()->for($petition)->create();

        $this->assertNotNull($event->id);
        $this->assertNotNull($event->petition_id);
        $this->assertNotNull($event->type);
        $this->assertNotNull($event->date);
    }

    #[Test]
    public function testMultiplePetitionEventsCanBeCreatedForSamePetition(): void
    {
        $petition = Petition::factory()->create();

        $event1 = PetitionEvent::factory()
            ->for($petition)
            ->withType(PetitionEventType::PRIMARY_DECISION)
            ->create();

        $event2 = PetitionEvent::factory()
            ->for($petition)
            ->withType(PetitionEventType::RECEIPT_OF_OBJECTION)
            ->create();

        $this->assertCount(2, $petition->petitionEvents);
        $this->assertEquals(PetitionEventType::PRIMARY_DECISION, $event1->type);
        $this->assertEquals(PetitionEventType::RECEIPT_OF_OBJECTION, $event2->type);
    }

    #[Test]
    public function testPetitionEventRelationshipIsPolymorphic(): void
    {
        $petition = Petition::factory()->create();
        $event = PetitionEvent::factory()->for($petition)->create();

        $fetchedEvent = PetitionEvent::find($event->id, ['*']);

        $this->assertEquals((string) $petition->id, (string) $fetchedEvent->petition->id);
    }
}
