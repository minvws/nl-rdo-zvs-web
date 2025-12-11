<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionAssignedUserControllerTest extends FeatureTestCase
{
    public function testEditAssignedUser(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('form');
    }

    public function testEditAssignedUserNotFound(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testViewAssignedUser(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk();
    }

    public function testUpdateAssignedUser(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        $this->assertEquals($user->id, $petition->assignedUser->id);
    }

    public function testUpdateAssignedUserAllowsAssignedUserToBeNull(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => null,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $this->assertNull($petition->assigned_user_id);
    }

    public function testUpdateAssignedUserWithHtmx(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertOk();

        $petition->refresh();
        $this->assertEquals($user->id, $petition->assignedUser->id);
    }

    public function testUpdateAssignedUserNotFound(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'user_id' => $this->faker->uuid()->toString(),
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserNotFoundWithHtmx(): void
    {
        $department = Department::factory()->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'hx-target' => 'hx-target',
                'user_id' => $this->faker->uuid()->toString(),
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserIdInvalidWithHtmx(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRouteAsHtmx(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'hx-target' => 'hx-target',
                'user_id' => $this->faker->word(),
            ])
            ->assertSessionHasErrors([
                'user_id' => 'Vul een geldig uuid in',
            ]);
    }

    public function testUpdateAssignedUserPetitionNotFound(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ], [
                'user_id' => null,
            ])
            ->assertNotFound();
    }

    public function testUpdateAssignedUserWithoutPermission(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $user = User::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_ASSIGN_USER_UPDATE, [
                'department' => $department,
                'petition' => $petition,
            ], [
                'user_id' => $user->id->toString(),
            ])
            ->assertForbidden();

        $petition->refresh();
    }
}
