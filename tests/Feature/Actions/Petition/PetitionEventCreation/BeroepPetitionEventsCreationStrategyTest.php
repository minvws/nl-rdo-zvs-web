<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\PetitionEventCreation;

use App\Actions\Petition\PetitionEventCreation\BeroepPetitionEventsCreationStrategy;
use App\Enums\Authorization\Permission;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function app;
use function now;

final class BeroepPetitionEventsCreationStrategyTest extends FeatureTestCase
{
    #[Test]
    public function testCreatesEventsBeroepPetition(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create(['type' => PetitionVariant::BEROEP]);
        PetitionStatus::factory()->recycle($department)->for($petitionType)->create();
        $petition = Petition::factory()->recycle($department)->for($petitionType)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $dateOfEntry = now()->subDays(10)->toDateString();
        $dateAppealedDecision = now()->addDays(20)->toDateString();

        $strategy = app(BeroepPetitionEventsCreationStrategy::class);

        // no events should be created for Beroep petitions
        $strategy->create($petition, [
            'date_of_entry' => $dateOfEntry,
            'date_appealed_decision' => $dateAppealedDecision,
        ], $user);

        $this->assertEquals(0, $petition->timelineItems()->count());
        $this->assertEquals(0, $petition->petitionEvents()->count());
    }
}
