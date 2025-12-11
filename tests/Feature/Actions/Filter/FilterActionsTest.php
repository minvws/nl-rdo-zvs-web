<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Filter;

use App\Actions\Filter\FilterClearAction;
use App\Actions\Filter\FilterGetAction;
use App\Actions\Filter\FilterHasAction;
use App\Actions\Filter\FilterSaveAction;
use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;
use Tests\Feature\FeatureTestCase;

final class FilterActionsTest extends FeatureTestCase
{
    public function testSavesFilterDataForUserAndDepartment(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $filterData = [
            'search' => 'test query',
            'status' => 'active',
        ];

        $action = new FilterSaveAction(new FilterClearAction());
        $action->execute($user, $department, 'petition', $filterData);

        $this->assertTrue(UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->exists());

        $filter = UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->first();

        $this->assertEquals($filterData, $filter->filter_data);
    }

    public function testRetrievesSavedFilterData(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $filterData = [
            'search' => 'test query',
            'category' => 'important',
        ];

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => $filterData,
        ]);

        $action = new FilterGetAction(new FilterClearAction());
        $result = $action->execute($user, $department, 'petition');

        $this->assertEquals($filterData, $result);
    }

    public function testReturnsEmptyArrayWhenNoFiltersExist(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $action = new FilterGetAction(new FilterClearAction());
        $result = $action->execute($user, $department, 'petition');

        $this->assertEquals([], $result);
    }

    public function testChecksIfFiltersExist(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $action = new FilterHasAction();
        $this->assertFalse($action->execute($user, $department, 'petition'));

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['search' => 'test'],
        ]);

        $this->assertTrue($action->execute($user, $department, 'petition'));
    }

    public function testClearsFilterData(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['search' => 'test'],
        ]);

        $action = new FilterClearAction();
        $action->execute($user, $department, 'petition');

        $this->assertFalse(UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->exists());
    }

    public function testClearsFiltersWhenSavingEmptyArray(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['search' => 'test'],
        ]);

        $action = new FilterSaveAction(new FilterClearAction());
        $action->execute($user, $department, 'petition', []);

        $this->assertFalse(UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->exists());
    }

    public function testUpdatesExistingFilterData(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        $originalData = ['search' => 'old query'];
        $updatedData = ['search' => 'new query', 'status' => 'active'];

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => $originalData,
        ]);

        $action = new FilterSaveAction(new FilterClearAction());
        $action->execute($user, $department, 'petition', $updatedData);

        $filter = UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->first();

        $this->assertEquals($updatedData, $filter->filter_data);
        $this->assertEquals(1, UserDepartmentFilter::count());
    }

    public function testFilterGetActionFiltersOutInvalidValues(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        // Save filter with invalid values
        $filterDataWithInvalidValues = [
            'search' => 'valid query',
            'status' => '',
            'category' => null,
            'type' => 'null',
            'valid_field' => 'valid value',
        ];

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => $filterDataWithInvalidValues,
        ]);

        $action = new FilterGetAction(new FilterClearAction());
        $result = $action->execute($user, $department, 'petition');

        // Should only return valid values
        $expectedResult = [
            'search' => 'valid query',
            'valid_field' => 'valid value',
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testFilterGetActionClearsInvalidFiltersFromDatabase(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();

        // Save filter with only invalid values
        $filterDataWithOnlyInvalidValues = [
            'status' => '',
            'category' => null,
            'type' => 'null',
        ];

        UserDepartmentFilter::create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => $filterDataWithOnlyInvalidValues,
        ]);

        $action = new FilterGetAction(new FilterClearAction());
        $result = $action->execute($user, $department, 'petition');

        // Should return empty array
        $this->assertEquals([], $result);

        // Should have cleared the invalid filter from database
        $this->assertFalse(UserDepartmentFilter::where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', 'petition')
            ->exists());
    }
}
