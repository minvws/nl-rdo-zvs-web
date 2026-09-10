<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\PetitionEventCreation\KlachtPetitionEventsCreationStrategy;
use App\Enums\Authorization\Permission;
use App\Enums\PetitionEventType;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function app;
use function now;

final class KlachtPetitionEventsCreationStrategyTest extends FeatureTestCase
{
    #[Test]
    public function testCreatesEventsKlachtPetition(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::KLACHT]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $dateOfEntry = now()->subDays(10)->toDateString();

        $strategy = app(KlachtPetitionEventsCreationStrategy::class);

        $strategy->create($petition, [
            'date_of_entry' => $dateOfEntry,
        ], $user);

        $this->assertDatabaseHas(PetitionEvent::class, [
            'petition_id' => $petition->id,
            'type' => PetitionEventType::RECEIPT_OF_COMPLAINT->value,
        ]);

        $this->assertEquals(
            [
                PetitionEventType::RECEIPT_OF_COMPLAINT,
            ],
            $petition->petitionEvents->map(
                static fn (PetitionEvent $event) => $event->type,
            )->toArray(),
        );
    }

    #[Test]
    public function testCreatesTimelineItemWhenEventsCreated(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::KLACHT]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $dateOfEntry = now()->subDays(10)->toDateString();

        $strategy = app(KlachtPetitionEventsCreationStrategy::class);

        $strategy->create($petition, [
            'date_of_entry' => $dateOfEntry,
        ], $user);

        $timelineItems = $petition->timelineItems()->get();
        $this->assertTrue($timelineItems->isNotEmpty());

        $createdItem = $timelineItems->first();
        $this->assertNotNull($createdItem);
        $this->assertEquals('petition_events_created', $createdItem->type->value);
        $this->assertEquals($user->id, $createdItem->user_id);
        $this->assertCount(1, $createdItem->data['event_types']);
    }
}
