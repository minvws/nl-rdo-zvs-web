<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionCustomPropertyFilterTest extends FeatureTestCase
{
    public function testFilterUiShowsCustomPropertiesInUseByDepartment(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $property = CustomPetitionProperty::factory()->for($petitionType)->create(['name' => 'Chatbesluit']);
        $petition = Petition::factory()->recycle($department)->create();
        $petition->customPetitionProperties()->attach($property);
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertSee('Eigenschappen')
            ->assertSee('Chatbesluit');
    }

    public function testFilterUiDoesNotShowCustomPropertiesFromOtherDepartments(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();
        $petitionType2 = PetitionType::factory()->recycle($department2)->create();
        $property = CustomPetitionProperty::factory()->for($petitionType2)->create(['name' => 'Andere afdeling eigenschap']);
        Petition::factory()->recycle($department2)->create()
            ->customPetitionProperties()->attach($property);
        $authUser = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department1->slug])
            ->assertOk()
            ->assertDontSee('Andere afdeling eigenschap');
    }

    public function testFilterUiHidesEigenschappenWhenNoneInUse(): void
    {
        $department = Department::factory()->create();
        Petition::factory()->recycle($department)->create();
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department->slug])
            ->assertOk()
            ->assertDontSee('Eigenschappen');
    }

    public function testFilterFiltersCorrectlyOnCustomProperty(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $property = CustomPetitionProperty::factory()->for($petitionType)->create(['name' => 'Chatbesluit']);
        $petitionWith = Petition::factory()->recycle($department)->create(['number' => 'REF-WITH-PROP']);
        Petition::factory()->recycle($department)->create(['number' => 'REF-WITHOUT-PROP']);
        $petitionWith->customPetitionProperties()->attach($property);
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => ['custom_property' => $property->id->toString()],
            ])
            ->assertOk()
            ->assertSee('REF-WITH-PROP')
            ->assertDontSee('REF-WITHOUT-PROP');
    }

    public function testFilterCombinesWithExistingFilters(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()->recycle($department)->create();
        $property = CustomPetitionProperty::factory()->for($petitionType)->create(['name' => 'Chatbesluit']);
        $matchingPetition = Petition::factory()->recycle($department)->create(['number' => 'REF-MATCH']);
        $matchingPetition->customPetitionProperties()->attach($property);
        $onlyPropPetition = Petition::factory()->recycle($department)->create(['number' => 'REF-PROP-ONLY']);
        $onlyPropPetition->customPetitionProperties()->attach($property);
        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_READ)->fullyVerified()->create();

        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department->slug,
                'filter' => ['custom_property' => $property->id->toString(), 'search' => 'REF-MATCH'],
            ])
            ->assertOk()
            ->assertSee('REF-MATCH')
            ->assertDontSee('REF-PROP-ONLY');
    }
}
