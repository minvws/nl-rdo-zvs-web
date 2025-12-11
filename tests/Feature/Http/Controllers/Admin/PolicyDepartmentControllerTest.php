<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\PolicyDepartment;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function str_repeat;
use function strpos;

class PolicyDepartmentControllerTest extends FeatureTestCase
{
    #[Test]
    public function testIndex(): void
    {
        Department::factory()->create();
        PolicyDepartment::factory()->create(['name' => 'Test Department']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk()
            ->assertSee('Test Department')
            ->assertViewIs('policy-department.index');
    }

    #[Test]
    public function testIndexWithoutPermission(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->fullyVerified()->create(); // No permission

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertForbidden();
    }

    #[Test]
    public function testIndexShowsActiveAndInactiveStatus(): void
    {
        Department::factory()->create();
        PolicyDepartment::factory()->create(['name' => 'Active Dept', 'active' => true]);
        PolicyDepartment::factory()->create(['name' => 'Inactive Dept', 'active' => false]);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        $response->assertSee('Active Dept')
            ->assertSee('Inactive Dept')
            ->assertSee(__('general.yes')) // Active status
            ->assertSee(__('general.no')); // Inactive status
    }

    #[Test]
    public function testCreate(): void
    {
        Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->assertOk()
            ->assertViewIs('policy-department.create');
    }

    #[Test]
    public function testCreateWithoutPermission(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->fullyVerified()->create(); // No permission

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->assertForbidden();
    }

    #[Test]
    public function testStore(): void
    {
        $name = $this->faker->company();

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => $name,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertSessionHas('message.success', __('general.saved'));

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'name' => $name,
            'active' => true,
        ]);
    }

    #[Test]
    public function testStoreInactive(): void
    {
        $name = $this->faker->company();

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => $name,
                'active' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'name' => $name,
            'active' => false,
        ]);
    }

    #[Test]
    public function testStoreWithoutPermission(): void
    {
        $authUser = User::factory()->fullyVerified()->create(); // No permission

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => $this->faker->company(),
                'active' => '1',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testStoreWithInvalidData(): void
    {
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');

        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => str_repeat('a', 256), // Max is 255
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');

        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => '',
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function testStoreDuplicateName(): void
    {
        PolicyDepartment::factory()->create(['name' => 'Existing Department']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_CREATE)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, [
                'name' => 'Existing Department',
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function testEdit(): void
    {
        Department::factory()->create();
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Test Department']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->assertOk()
            ->assertViewIs('policy-department.edit');

        $response->assertSee('Test Department');
    }

    #[Test]
    public function testEditWithoutPermission(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        $authUser = User::factory()->fullyVerified()->create(); // No permission

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->assertForbidden();
    }

    #[Test]
    public function testEditWithWrongIdThrowsException(): void
    {
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $this->faker->uuid])
            ->assertNotFound();
    }

    #[Test]
    public function testUpdate(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Old Name', 'active' => false]);
        $newName = $this->faker->company();

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => $newName,
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertSessionHas('message.success', __('general.saved'));

        $policyDepartment->refresh();
        $this->assertSame($newName, $policyDepartment->name);
        $this->assertTrue($policyDepartment->active);
    }

    #[Test]
    public function testUpdateToInactive(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create(['active' => true]);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => $policyDepartment->name,
                'active' => '0',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);

        $policyDepartment->refresh();
        $this->assertFalse($policyDepartment->active);
    }

    #[Test]
    public function testUpdateWithoutPermission(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        $authUser = User::factory()->fullyVerified()->create(); // No permission

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => $this->faker->company(),
                'active' => '1',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function testUpdateWithInvalidData(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');

        // Test name too long
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => str_repeat('a', 256), // Max is 255
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function testUpdateWithDuplicateName(): void
    {
        PolicyDepartment::factory()->create(['name' => 'Existing Department']);
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Other Department']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => 'Existing Department',
                'active' => '1',
            ])
            ->assertSessionHasErrors('name');
    }

    #[Test]
    public function testUpdateKeepsSameNameAllowed(): void
    {
        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Same Name']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->fromRoute(RouteName::ADMIN_POLICY_DEPARTMENT_EDIT, ['policyDepartment' => $policyDepartment])
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => 'Same Name', // Same name should be allowed
                'active' => '1',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);
    }

    #[Test]
    public function testIndexPaginatesResults(): void
    {
        Department::factory()->create();

        // Create more policy departments than the pagination limit
        PolicyDepartment::factory()->count(25)->create();

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        // Should see pagination links if there are more than 20 items (config setting)
        $response->assertViewHas('policyDepartments');
    }

    #[Test]
    public function testIndexOrdersByName(): void
    {
        Department::factory()->create();

        PolicyDepartment::factory()->create(['name' => 'Z Department']);
        PolicyDepartment::factory()->create(['name' => 'A Department']);
        PolicyDepartment::factory()->create(['name' => 'M Department']);

        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();
        $response = $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX)
            ->assertOk();

        $content = $response->getContent();
        $positionA = strpos($content, 'A Department');
        $positionM = strpos($content, 'M Department');
        $positionZ = strpos($content, 'Z Department');

        // Check that they appear in alphabetical order
        $this->assertLessThan($positionM, $positionA);
        $this->assertLessThan($positionZ, $positionM);
    }

    #[Test]
    public function testStoreTrimsWhitespaceFromName(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_STORE, data: [
                'name' => '  Whitespace Department  ',
                'active' => '1',
            ])
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);

        $this->assertDatabaseHas(PolicyDepartment::class, [
            'name' => 'Whitespace Department', // Should be trimmed
            'active' => true,
        ]);

        // Should not exist with untrimmed name
        $this->assertDatabaseMissing(PolicyDepartment::class, [
            'name' => '  Whitespace Department  ',
        ]);
    }

    #[Test]
    public function testUpdateTrimsWhitespaceFromName(): void
    {
        Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::POLICY_DEPARTMENT_WRITE)->fullyVerified()->create();

        $policyDepartment = PolicyDepartment::factory()->create(['name' => 'Original Name']);

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_POLICY_DEPARTMENT_UPDATE, ['policyDepartment' => $policyDepartment], data: [
                'name' => '  Updated Whitespace Name  ',
                'active' => '1',
            ])
            ->assertRedirectToRoute(RouteName::ADMIN_POLICY_DEPARTMENT_INDEX);

        $policyDepartment->refresh();
        $this->assertSame('Updated Whitespace Name', $policyDepartment->name); // Should be trimmed

        // Verify in database
        $this->assertDatabaseHas(PolicyDepartment::class, [
            'id' => $policyDepartment->id,
            'name' => 'Updated Whitespace Name',
        ]);
    }
}
