<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\PolicyDepartment;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function route;
use function str_repeat;
use function strpos;

class PolicyDepartmentAdminWorkflowTest extends FeatureTestCase
{
    #[Test]
    public function testCompleteAdminWorkflow(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk()
            ->assertViewIs('policy-department.index');

        $response->assertSee(__('policy_department.no_records'));

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->assertOk()
            ->assertViewIs('policy-department.create');

        $departmentName = $this->faker->company();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => $departmentName,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertSessionHas('message.success', __('general.saved'));

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        $response->assertSee($departmentName)
            ->assertSee(__('general.yes')); // Should show as active

        $policyDepartment = PolicyDepartment::query()->where('name', $departmentName)->first();
        $this->assertNotNull($policyDepartment);

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->assertOk()
            ->assertViewIs('policy-department.edit')
            ->assertSee($departmentName);

        $updatedName = $this->faker->company();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => $updatedName,
                'active' => '0', // Make inactive
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertSessionHas('message.success', __('general.saved'));

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        $response->assertSee($updatedName)
            ->assertSee(__('general.no')) // Should show as inactive
            ->assertDontSee($departmentName); // Old name should not appear

        $policyDepartment->refresh();
        $this->assertSame($updatedName, $policyDepartment->name);
        $this->assertFalse($policyDepartment->active);
    }

    #[Test]
    public function testAdminNavigationIntegration(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->withPermissions(
            Permission::POLICY_DEPARTMENT_WRITE,
            Permission::ADMIN_PANEL_VIEW,
        )->fullyVerified()->create();

        $response = $this->beUser($authUser)
            ->get('/admin') // Admin panel home
            ->assertOk();

        $response->assertSee(__('policy_department.model_plural'))
            ->assertSee(route(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX));
    }

    #[Test]
    public function testPermissionEnforcement(): void
    {
        Department::factory()->create();
        $policyDepartment = PolicyDepartment::factory()->create();

        $userWithoutPermission = User::factory()->fullyVerified()->create();

        $this->beUser($userWithoutPermission)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertForbidden();

        $this->beUser($userWithoutPermission)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->assertForbidden();

        $this->beUser($userWithoutPermission)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->assertForbidden();

        $this->beUser($userWithoutPermission)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => $this->faker->company(),
                'active' => '1',
            ])
            ->assertForbidden();

        $this->beUser($userWithoutPermission)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => $this->faker->company(),
                'active' => '0',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testValidationErrorsShowProperly(): void
    {
        Department::factory()->create();
        PolicyDepartment::factory()->create(['name' => 'Existing Department']);
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => 'Existing Department', // Duplicate name
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');

        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Other Department']);
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => str_repeat('a', 256), // Too long
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function testActivePolicyDepartmentsFilterInPetitionForm(): void
    {
        Department::factory()->create();

        $activeDepartment = PolicyDepartment::factory()->create(['name' => 'Active Department', 'active' => true]);
        $inactiveDepartment = PolicyDepartment::factory()->create(['name' => 'Inactive Department', 'active' => false]);

        $activeDepartments = PolicyDepartment::query()->active()->get();

        $this->assertTrue($activeDepartments->contains($activeDepartment));
        $this->assertFalse($activeDepartments->contains($inactiveDepartment));
    }

    #[Test]
    public function testMultiplePolicyDepartmentsOrdering(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        PolicyDepartment::factory()->create(['name' => 'Z Department']);
        PolicyDepartment::factory()->create(['name' => 'A Department']);
        PolicyDepartment::factory()->create(['name' => 'M Department']);

        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        $content = $response->getContent();

        $positionA = strpos($content, 'A Department');
        $positionM = strpos($content, 'M Department');
        $positionZ = strpos($content, 'Z Department');

        $this->assertNotFalse($positionA);
        $this->assertNotFalse($positionM);
        $this->assertNotFalse($positionZ);

        $this->assertLessThan($positionM, $positionA);
        $this->assertLessThan($positionZ, $positionM);
    }
}
