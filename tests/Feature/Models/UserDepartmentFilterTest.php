<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;
use Tests\Feature\FeatureTestCase;

class UserDepartmentFilterTest extends FeatureTestCase
{
    public function testBelongsToUser(): void
    {
        $user = User::factory()->create();
        $department = Department::factory()->create();
        $userDepartmentFilter = $user->departmentFilters()->create([
            'department_id' => $department->id,
            'filterable_type' => 'petition',
            'filter_data' => ['status' => 'active'],
        ]);
        $this->assertInstanceOf(UserDepartmentFilter::class, $userDepartmentFilter);
        $this->assertInstanceOf(User::class, $userDepartmentFilter->user);
        $this->assertEquals($user->id->toString(), $userDepartmentFilter->user->id->toString());
        $this->assertEquals($department->departmentFilters()->first()->id, $userDepartmentFilter->id);
        $this->assertEquals($department->id, $userDepartmentFilter->department->id);
    }
}
