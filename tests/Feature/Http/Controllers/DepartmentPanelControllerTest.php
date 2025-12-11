<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DepartmentPanelControllerTest extends FeatureTestCase
{
    #[Test]
    public function testDepartmentList(): void
    {
        Department::factory()->create();

        $user = User::factory()->withPermissions(Permission::DEPARTMENT_READ)->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_SHOW)
            ->assertOk()
            ->assertViewIs('form');
    }

    #[Test]
    public function testDepartmentListWithHtmx(): void
    {
        $user = User::factory()->withPermissions(Permission::DEPARTMENT_READ)->fullyVerified()->create();
        $this->beUser($user)
            ->getByRouteAsHtmx(RouteName::DEPARTMENTS_SHOW)
            ->assertOk();
    }

    #[Test]
    public function testDepartmentCanNotBeVisitedWithoutPermission(): void
    {
        Department::factory()->create();

        $user = User::factory()->fullyVerified()->create();
        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_SHOW)
            ->assertForbidden();
    }
}
