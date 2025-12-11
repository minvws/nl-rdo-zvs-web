<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\GlobalRole;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class DashboardControllerTest extends FeatureTestCase
{
    public function testDashboardPageIsDisplayed(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissionsAndDepartment($department)->fullyVerified()->create();
        $this->beUser($user, true, $department)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);
    }

    public function testAdminWithoutDepartmentRolesIsNotAllowed(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->withGlobalRoles(GlobalRole::ADMINISTRATOR)
            ->create();
        $this->beUser($user)
            ->getByRoute('dashboard')
            ->assertStatus(403);
    }
}
