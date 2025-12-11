<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class AuthenticatedTest extends FeatureTestCase
{
    public function testDashboardIsAccessibleForVerifiedUserWithValidSession(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissionsAndDepartment($department)->fullyVerified()->create();
        $this->beUser($user, true, $department)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);
    }

    public function testDashboardisNotAccessibleWhenNotAuthenticated(): void
    {
        $this->getByRoute('dashboard')
            ->assertRedirectToRoute(RouteName::LOGIN);
    }
}
