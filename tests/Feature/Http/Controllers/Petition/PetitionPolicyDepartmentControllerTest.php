<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PolicyDepartment;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionPolicyDepartmentControllerTest extends FeatureTestCase
{
    public function testEditPolicyDepartment(): void
    {
        $petition = Petition::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, [
                'department' => $petition->department->slug,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditPolicyDepartmentWhenPetitionNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testShowPolicyDepartment(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    public function testUpdatePolicyDepartment(): void
    {
        $petition = Petition::factory()->create();
        $policyDepartment = PolicyDepartment::factory()->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($petition->department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $petition->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE, [
                'department' => $petition->department,
                'petition' => $petition,
            ], [
                'policy_department_ids' => [$policyDepartment->id->toString()],
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $petition->department->slug,
                'petition' => $petition,
            ]);

        $petition->refresh();
        $this->assertEquals($policyDepartment->id->toString(), $petition->policyDepartments->first()->id->toString());
    }

    public function testUpdatePolicyDepartmentToNone(): void
    {
        $petition = Petition::factory()->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($petition->department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $petition->department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE, [
                'department' => $petition->department,
                'petition' => $petition,
            ], [])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $petition->department->slug,
                'petition' => $petition,
            ]);

        $petition->refresh();
        $this->assertEmpty($petition->policyDepartments);
    }

    public function testUpdatePolicyDepartmentWithHtmx(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $policyDepartment = PolicyDepartment::factory()->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($petition->department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $petition->department)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE, [
                'department' => $petition->department,
                'petition' => $petition,
            ], [
                'policy_department_ids' => [$policyDepartment->id->toString()],
            ])
            ->assertOk();

        $petition->refresh();
        $this->assertEquals($policyDepartment->id->toString(), $petition->policyDepartments->first()->id->toString());
    }

    public function testUpdatePolicyDepartmentWhenPetitionNotFound(): void
    {
        $petition = Petition::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_UPDATE, [
                'department' => $petition->department->slug,
                'petition' => $this->faker->uuid(),
            ], [
                'policy_department_ids' => [],
            ])->assertNotFound();
    }

    public function testEditPolicyDepartmentOnlyShowsActiveDepartments(): void
    {
        $activePolicyDepartment = PolicyDepartment::factory()->create(['active' => true]);
        $inactivePolicyDepartment = PolicyDepartment::factory()->create(['active' => false]);

        $petition = Petition::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_POLICY_DEPARTMENT_EDIT, [
                'department' => $petition->department->slug,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');

        $policyDepartments = $response->viewData('policyDepartments');

        $this->assertTrue($policyDepartments->contains('id', $activePolicyDepartment->id));
        $this->assertFalse($policyDepartments->contains('id', $inactivePolicyDepartment->id));
    }
}
