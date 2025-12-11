<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\OptionalFormFieldSetting;
use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

use function sprintf;

class PetitionStoreControllerTest extends FeatureTestCase
{
    public function testStore(): void
    {
        $department = Department::factory()
            ->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create([
                'type' => $this->faker->randomElement([
                    PetitionTypeType::BEROEP,
                    PetitionTypeType::WOO_VERZOEK,
                ]),
            ]);
        $petitionStatus = PetitionStatus::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();

        $name = $this->faker->name();
        $dateOfEntry = $this->faker->calendarDate();
        $description = $this->faker->optional()->text();

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::REQUIRED,
            'description' => OptionalFormFieldSetting::OPTIONAL,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::EXCLUDED,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_STORE,
                [
                    'department' => $department,
                    'petitionType' => $petitionType,
                ],
                [
                    'petition_status_id' => $petitionStatus->id->toString(),
                    'name' => $name,
                    'date_of_entry' => $dateOfEntry->format('Y-m-d'),
                    'description' => $description,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('petition_statuses_history_entries', [
            'petition_status_id' => $petitionStatus->id,
        ]);

        $this->assertDatabaseHas(Petition::class, [
            'name' => $name,
            'petition_type_id' => $petitionType->id,
            'date_of_entry' => $dateOfEntry->format('Y-m-d H:i:s+00'),
            'description' => $description,
        ]);
    }

    public function testStoreWithDisabledFields(): void
    {
        $department = Department::factory()
            ->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create([
                'type' => $this->faker->randomElement([
                    PetitionTypeType::BEROEP,
                    PetitionTypeType::WOO_VERZOEK,
                ]),
            ]);
        $petitionStatus = PetitionStatus::factory()
            ->recycle($department)
            ->for($petitionType)
            ->create();

        $petitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create();

        ConfigHelper::set(sprintf('petition_type_type.%s.optional_form_fields', $petitionType->type->value), [
            'name' => OptionalFormFieldSetting::EXCLUDED,
            'description' => OptionalFormFieldSetting::EXCLUDED,
            'date_appealed_decision' => OptionalFormFieldSetting::EXCLUDED,
            'petition_category_id' => OptionalFormFieldSetting::REQUIRED,
        ]);

        $dateOfEntry = $this->faker->calendarDate();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_STORE,
                [
                    'department' => $department,
                    'petitionType' => $petitionType,
                ],
                [
                    'name' => $this->faker->uuid(),
                    'petition_status_id' => $petitionStatus->id->toString(),
                    'petition_category_id' => $petitionCategory->id->toString(),
                    'date_of_entry' => $dateOfEntry->format('Y-m-d'),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('petition_statuses_history_entries', [
            'petition_status_id' => $petitionStatus->id,
        ]);

        $this->assertDatabaseHas(Petition::class, [
            'name' => $petitionCategory->name,
            'petition_type_id' => $petitionType->id,
            'date_of_entry' => $dateOfEntry->format('Y-m-d H:i:s+00'),
            'description' => null,
        ]);
    }
}
