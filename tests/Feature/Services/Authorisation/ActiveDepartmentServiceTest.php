<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Authorisation;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use App\Services\Authorisation\ActiveDepartmentService;
use Tests\Feature\FeatureTestCase;

class ActiveDepartmentServiceTest extends FeatureTestCase
{
    public function testHasActiveDepartmentWithLastVisitedDepartment(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => $department->id]);

        $this->beUser($user);

        $activeDepartmentService = $this->app->make(ActiveDepartmentService::class);
        $result = $activeDepartmentService->hasActiveDepartment();

        $this->assertTrue($result);
    }

    public function testHasActiveDepartmentWithoutLastVisitedDepartment(): void
    {
        $user = User::factory()->create(['last_visited_department_id' => null]);

        $this->beUser($user);

        $activeDepartmentService = $this->app->make(ActiveDepartmentService::class);
        $result = $activeDepartmentService->hasActiveDepartment();

        $this->assertFalse($result);
    }

    public function testDetermineActiveDepartmentWithWritePermission(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['last_visited_department_id' => null]);

        $user->departments()->attach($department, ['role' => DepartmentRole::WRITE]);

        $activeDepartmentService = $this->app->make(ActiveDepartmentService::class);
        $result = $activeDepartmentService->determineActiveDepartment($user);

        $this->assertEquals($department->id, $result->id);
    }

    public function testGetActiveDepartmentWithoutUser(): void
    {
        $activeDepartmentService = $this->app->make(ActiveDepartmentService::class);
        $result = $activeDepartmentService->getActiveDepartment();

        $this->assertNull($result);
    }

    public function testHasActiveDepartmentWithoutUser(): void
    {
        $activeDepartmentService = $this->app->make(ActiveDepartmentService::class);
        $result = $activeDepartmentService->hasActiveDepartment();

        $this->assertFalse($result);
    }
}
