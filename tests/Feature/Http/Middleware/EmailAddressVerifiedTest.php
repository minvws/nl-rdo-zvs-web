<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class EmailAddressVerifiedTest extends FeatureTestCase
{
    public function testDashboardIsAccessibleIfEmailVerified(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissionsAndDepartment($department)->fullyVerified()->create();
        $this->beUser($user, true, $department)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_INDEX, ['department' => $department]);
    }

    public function testDashboardIsNotAccessibleIfEmailNotVerified(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->unverifiedEmail() // more explicit than unverified()
            ->create();

        $this->beUser($user)
            ->getByRoute('dashboard')
            ->assertRedirectToRoute('verification.notice');
    }
}
