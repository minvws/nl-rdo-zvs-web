<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;
use Tests\Feature\FeatureTestCase;

use function route;

class PetitionFilterTest extends FeatureTestCase
{
    public function testSavesFiltersWhenPostingToFilterRoute(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'slug' => 'test-department',
            'abbreviation' => 'TD',
            'hide_column_defaults' => '',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $filterData = [
            'search' => 'test petition',
            'status' => 'active',
        ];

        $response = $this->beUser($user, true, $department)
            ->post(route(RouteName::DEPARTMENTS_PETITIONS_INDEX_FILTER, ['department' => $department]), [
                'filter' => $filterData,
            ]);

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
            'department' => $department,
            'filter' => $filterData,
        ]));

        $this->assertDatabaseHas('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
        ]);

        $savedFilter = UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->first();

        $this->assertEquals($filterData, $savedFilter->filter_data);
    }

    public function testRedirectsToSavedFiltersWhenAccessingIndexWithoutFilterParameters(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'slug' => 'test-department',
            'abbreviation' => 'TD',
            'hide_column_defaults' => '',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $savedFilterData = [
            'search' => 'saved search',
            'status' => 'pending',
        ];

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => $savedFilterData,
        ]);

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]));

        $response->assertRedirect(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
            'department' => $department,
            'filter' => $savedFilterData,
        ]));
    }

    public function testClearsSavedFiltersWhenAccessingIndexWithClearFiltersParameter(): void
    {
        // Arrange
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'slug' => 'test-department',
            'abbreviation' => 'TD',
            'hide_column_defaults' => '',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['search' => 'test'],
        ]);

        // Verify filters exist before clearing
        $this->assertDatabaseHas('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
        ]);

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department,
                'filter' => 'clear',
            ]));

        $response->assertOk();

        $this->assertDatabaseMissing('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
        ]);
    }

    public function testHandlesClearingFilterValuesWithoutRedirectLoop(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'slug' => 'test-department',
            'abbreviation' => 'TD',
            'hide_column_defaults' => '',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['search' => 'test', 'status' => 'active'],
        ]);

        $this->assertDatabaseHas('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
        ]);

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, [
                'department' => $department,
                'filter' => ['search' => '', 'status' => ''],
            ]));

        $response->assertOk();

        $this->assertDatabaseMissing('user_department_filters', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
        ]);
    }

    public function testShowsIndexWithoutFiltersWhenNoSavedFiltersExist(): void
    {
        $department = Department::factory()->create([
            'name' => 'Test Department',
            'slug' => 'test-department',
            'abbreviation' => 'TD',
            'hide_column_defaults' => '',
        ]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($user, true, $department)
            ->get(route(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]));

        $response->assertOk();
        $response->assertViewIs('petition.index');
    }
}
