<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition\TermCreation;

use App\Actions\Petition\TermCreation\WooVerzoekTermCreationStrategy;
use App\Enums\PetitionTypeType;
use App\Enums\TermType;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

class WooVerzoekTermCreationStrategyTest extends FeatureTestCase
{
    public function testCreatesFirstTermWithConfiguredSettings(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $dateOfEntry = $this->faker->calendarDate();
        $durationInDays = $this->faker->numberBetween(10, 100);
        $penaltyAmount = $this->faker->numberBetween(100, 1000);

        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => $durationInDays,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'penalty_amount_in_euros',
            'active' => true,
            'default_value' => $penaltyAmount,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ]);

        $attributes = [
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ];

        $strategy = $this->app->get(WooVerzoekTermCreationStrategy::class);
        $strategy->createTerms($petition, $attributes, $user);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'start_date' => $dateOfEntry->addDay()->format('Y-m-d'),
            'duration_in_days' => $durationInDays,
            'penalty_amount_in_euros' => $penaltyAmount,
        ]);
    }

    public function testUsesDefaultDurationWhenSettingInactive(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $dateOfEntry = $this->faker->calendarDate();
        $defaultDuration = $this->faker->numberBetween(10, 50);

        ConfigHelper::set('petition_term.default_first_term_duration_in_days', $defaultDuration);

        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => false,
            'default_value' => 999,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'penalty_amount_in_euros',
            'active' => true,
            'default_value' => 0,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ]);

        $attributes = [
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ];

        $strategy = $this->app->get(WooVerzoekTermCreationStrategy::class);
        $strategy->createTerms($petition, $attributes, $user);

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'type' => TermType::FIRST,
            'duration_in_days' => $defaultDuration,
        ]);
    }

    public function testCalculatesCorrectStartDate(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);
        $this->actingAs($user);

        $dateOfEntry = $this->faker->calendarDate();

        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'duration_in_days',
            'active' => true,
            'default_value' => 10,
        ]);
        DepartmentTermTypeSetting::factory()->create([
            'department_id' => $department->id,
            'term_type' => TermType::FIRST,
            'field' => 'penalty_amount_in_euros',
            'active' => true,
            'default_value' => 0,
        ]);

        $petitionType = PetitionType::factory()->for($department)->create([
            'type' => PetitionTypeType::WOO_VERZOEK,
        ]);
        $petition = Petition::factory()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionType->id,
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ]);

        $attributes = [
            'date_of_entry' => $dateOfEntry->format('Y-m-d'),
        ];

        $strategy = $this->app->get(WooVerzoekTermCreationStrategy::class);
        $strategy->createTerms($petition, $attributes, $user);

        $expectedStartDate = $dateOfEntry->addDay()->format('Y-m-d');

        $this->assertDatabaseHas(PetitionTerm::class, [
            'petition_id' => $petition->id,
            'start_date' => $expectedStartDate,
        ]);
    }
}
