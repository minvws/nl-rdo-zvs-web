<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionArchivedRouteSecurityTest extends FeatureTestCase
{
    #[Test]
    public function testCannotAccessCustomDatesEditForArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testCanAccessCustomDatesEditForNonArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    #[Test]
    public function testCannotUpdateCustomDatesForArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_CUSTOM_DATES_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'custom_dates' => [],
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testCannotAccessMultipleEditingRoutesForArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => Carbon::now(),
        ]);

        $testUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $user = $this->beUser($testUser, true, $department);

        // Test properties edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        // Test assigned user edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        // Test external URLs edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        // Test policy department edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();

        // Test status change edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertForbidden();
    }

    #[Test]
    public function testCanAccessMultipleEditingRoutesForNonArchivedPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create([
            'archived_at' => null,
        ]);

        $testUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $user = $this->beUser($testUser, true, $department);

        // Test properties edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PROPERTIES_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertOk();

        // Test assigned user edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_PRIMARY_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertOk();

        // Test external URLs edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_EXTERNAL_URLS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertOk();

        // Test policy department edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertOk();

        // Test status change edit
        $user->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
            'department' => $department,
            'petition' => $petition,
        ])->assertOk();
    }
}
