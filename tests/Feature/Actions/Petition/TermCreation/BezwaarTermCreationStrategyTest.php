<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\TermCreation;

use App\Actions\Petition\TermCreation\BezwaarTermCreationStrategy;
use App\Enums\PetitionTypeType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class BezwaarTermCreationStrategyTest extends FeatureTestCase
{
    public function testCreatesObjectionPeriodAndFirstTerm(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $objectionDuration = 6;
        $firstTermDuration = 12;

        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::OBJECTION_PERIOD,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $objectionDuration,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDuration,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionTypeType::BEZWAAR,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
        ]);

        $dateAppealedDecision = '2025-01-01';
        $dateOfEntry = '2025-01-20';

        $strategy = $this->app->get(BezwaarTermCreationStrategy::class);
        $strategy->createTerms($petition, [
            'date_of_entry' => $dateOfEntry,
            'date_appealed_decision' => $dateAppealedDecision,
        ], $user);

        // Objection period: starts day after dateAppealedDecision
        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::OBJECTION_PERIOD,
            'start_date' => '2025-01-02',
            'duration_in_days' => $objectionDuration,
        ]);

        // First term: starts after objection period ends (2025-01-02 + 6 = 2025-01-08),
        // which is before dateOfEntry (2025-01-20), so starts at dateOfEntry + 1 = 2025-01-21
        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => '2025-01-21',
            'duration_in_days' => $firstTermDuration,
        ]);
    }

    public function testFirstTermStartsAfterObjectionPeriodWhenItExceedsDateOfEntry(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $objectionDuration = 30;
        $firstTermDuration = 12;

        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::OBJECTION_PERIOD,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $objectionDuration,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $firstTermDuration,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionTypeType::BEZWAAR,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
        ]);

        $dateAppealedDecision = '2025-01-01';
        $dateOfEntry = '2025-01-05';

        $strategy = $this->app->get(BezwaarTermCreationStrategy::class);
        $strategy->createTerms($petition, [
            'date_of_entry' => $dateOfEntry,
            'date_appealed_decision' => $dateAppealedDecision,
        ], $user);

        // Objection period ends on 2025-01-02 + 30 = 2025-02-01, which is after dateOfEntry,
        // so first term starts at objection period end date: 2025-02-01
        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => '2025-02-01',
            'duration_in_days' => $firstTermDuration,
        ]);
    }
}
