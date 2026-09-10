<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\TermCreation;

use App\Actions\Petition\TermCreation\KlachtTermCreationStrategy;
use App\Enums\PetitionVariant;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class KlachtTermCreationStrategyTest extends FeatureTestCase
{
    public function testCreatesFirstTerm(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $firstTermDuration = 12;
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDuration,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionVariant::KLACHT,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
        ]);

        $dateOfEntry = '2025-01-20';

        $strategy = $this->app->get(KlachtTermCreationStrategy::class);
        $strategy->createTerms($petition, [
            'date_of_entry' => $dateOfEntry,
        ], $user);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => '2025-01-21',
            'duration_in_days' => $firstTermDuration,
        ]);
    }
}
