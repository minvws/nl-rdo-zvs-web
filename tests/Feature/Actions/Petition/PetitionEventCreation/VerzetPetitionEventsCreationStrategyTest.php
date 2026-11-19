<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\PetitionEventCreation\VerzetPetitionEventsCreationStrategy;
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

final class VerzetPetitionEventsCreationStrategyTest extends FeatureTestCase
{
    #[Test]
    public function testCreatesTheReceiptOfOppositionOnTheDateOfEntry(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::VERZET]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $dateOfEntry = now()->subDays(10)->toDateString();

        app(VerzetPetitionEventsCreationStrategy::class)->create($petition, [
            'date_of_entry' => $dateOfEntry,
        ], $user);

        $events = $petition->petitionEvents()->get();
        $this->assertCount(1, $events);

        $event = $events->first();
        $this->assertInstanceOf(PetitionEvent::class, $event);
        $this->assertSame(PetitionEventType::RECEIPT_OF_OPPOSITION, $event->type);
        $this->assertSame($dateOfEntry, $event->date->toDateString());
        $this->assertNull($event->duration);
    }
}
