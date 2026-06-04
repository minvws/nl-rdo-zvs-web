<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\TermCreation;

use App\Actions\Petition\TermCreation\BeroepTermCreationStrategy;
use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class BeroepTermCreationStrategyTest extends FeatureTestCase
{
    public function testDoesNotCreateAnyTerms(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionVariant::BEROEP,
        ]);
        $petitionStatus = PetitionStatus::factory()->create([
            'petition_type_id' => $petitionType->id,
        ]);

        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'petition_status_id' => $petitionStatus->id,
        ]);

        $attributes = [
            'date_of_entry' => $this->faker->calendarDate()->format('Y-m-d'),
        ];

        $strategy = $this->app->get(BeroepTermCreationStrategy::class);
        $strategy->createTerms($petition, $attributes, $user);

        $this->assertDatabaseMissing(PetitionTerm::class, [
            'petition_id' => $petition->id,
        ]);
    }

    public function testCanBeCalledMultipleTimesWithoutSideEffects(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionVariant::BEROEP,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
        ]);

        $attributes = [
            'date_of_entry' => $this->faker->calendarDate()->format('Y-m-d'),
        ];

        $strategy = $this->app->get(BeroepTermCreationStrategy::class);
        $strategy->createTerms($petition, $attributes, $user);
        $strategy->createTerms($petition, $attributes, $user);

        $termCount = PetitionTerm::query()->where('petition_id', $petition->id)->count();
        $this->assertSame(0, $termCount);
    }
}
