<?php

declare(strict_types=1);

namespace Tests\Feature\Services\User;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use App\Services\User\UserRoleService;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UserRoleServiceTest extends FeatureTestCase
{
    #[Test]
    public function testGetDepartmentRoles(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();

        $role = $this->faker->randomElement(DepartmentRole::cases());
        $department->users()->attach($user, ['role' => $role]);

        $userService = $this->app->make(UserRoleService::class);

        $result = $userService->getDepartmentRoles($user->id);

        $this->assertEquals([$department->id->toString() => [$role]], $result);
    }
}
