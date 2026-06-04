<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\PetitionVariant;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\Team;
use App\Models\User;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function sprintf;

class PetitionTeamControllerTest extends FeatureTestCase
{
    public function testStoreWithTeamAssignment(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create(['type' => PetitionVariant::BEROEP]);
        $petitionStatus = PetitionStatus::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();
        $team = Team::factory()
            ->recycle($department)
            ->create();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_STORE,
                [
                    'department' => $department,
                    'petitionType' => $petitionType,
                ],
                [
                    'petition_status_id' => $petitionStatus->id->toString(),
                    'name' => 'Test Petition with Team',
                    'date_of_entry' => '2024-01-01',
                    'description' => 'Test description',
                    'team_id' => $team->id->toString(),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Petition::class, [
            'name' => 'Test Petition with Team',
            'team_id' => $team->id,
        ]);
    }

    public function testUpdatePetitionTeam(): void
    {
        $department = Department::factory()->create();
        $category = PetitionCategory::factory()->recycle($department)->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create([
                'team_id' => null,
                'petition_category_id' => $category->id,
            ]);

        $team = Team::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'name' => $petition->name,
                    'team_id' => $team->id->toString(),
                    'petition_category_id' => $category->id->toString(),
                    'date_of_entry' => $petition->date_of_entry->format('Y-m-d'),
                    'date_appealed_decision' => '2024-02-01',
                ],
            )
            ->assertSessionHasNoErrors();

        $petition->refresh();
        $this->assertEquals($team->id, $petition->team_id);
    }

    public function testCannotAssignTeamFromDifferentDepartment(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType = PetitionType::factory()
            ->recycle($department1)
            ->create(['type' => PetitionVariant::BEROEP]);
        $petitionStatus = PetitionStatus::factory()
            ->recycle($department1)
            ->for($petitionType)
            ->create();

        $team = Team::factory()
            ->recycle($department2)
            ->create();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department1)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_STORE,
                [
                    'department' => $department1,
                    'petitionType' => $petitionType,
                ],
                [
                    'petition_status_id' => $petitionStatus->id->toString(),
                    'name' => 'Test Petition',
                    'date_of_entry' => '2024-01-01',
                    'team_id' => $team->id->toString(),
                ],
            )
            ->assertSessionHasErrors('team_id');
    }

    public function testPetitionTeamCanBeNull(): void
    {
        $department = Department::factory()->create();
        $category = PetitionCategory::factory()->recycle($department)->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create(['type' => PetitionVariant::BEROEP]);
        $petitionStatus = PetitionStatus::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();

        ConfigHelper::set(sprintf('petition_variant.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::OPTIONAL,
            'petition_category_id' => OptionalFormFieldSetting::REQUIRED,
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_STORE,
                [
                    'department' => $department,
                    'petitionType' => $petitionType,
                ],
                [
                    'petition_status_id' => $petitionStatus->id->toString(),
                    'petition_category_id' => $category->id->toString(),
                    'name' => 'Test Petition without Team',
                    'date_of_entry' => '2024-01-01',
                    'team_id' => null,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Petition::class, [
            'name' => 'Test Petition without Team',
            'team_id' => null,
        ]);
    }
}
