<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\User;

use App\Actions\User\UserUpdateDepartmentRolesAction;
use App\Events\DepartmentRolesAssignedEvent;
use App\Events\DepartmentRolesWithdrawnEvent;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\Feature\FeatureTestCase;

class UserUpdateDepartmentRolesActionTest extends FeatureTestCase
{
    public function testUpdateDepartmentRolesAssignsNewRoles(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $department = Department::factory()->create();
        $departmentRoles = [$department->id->toString() => ['read', 'write']];

        $action = new UserUpdateDepartmentRolesAction();
        $action->execute($user, $departmentRoles);

        $this->assertDatabaseHas('department_user', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'read',
        ]);

        $this->assertDatabaseHas('department_user', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'write',
        ]);

        Event::assertDispatched(DepartmentRolesAssignedEvent::class);
    }

    public function testUpdateDepartmentRolesWithdrawsRemovedRoles(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $department = Department::factory()->create();

        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'read',
        ]);

        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'write',
        ]);

        $newRoles = [$department->id->toString() => ['read']];

        $action = new UserUpdateDepartmentRolesAction();
        $action->execute($user, $newRoles);

        Event::assertDispatched(DepartmentRolesWithdrawnEvent::class, function ($event) {
            return $event->departmentUser->count() === 1; // 'write' were withdrawn
        });

        Event::assertNotDispatched(DepartmentRolesAssignedEvent::class);

        $this->assertDatabaseHas('department_user', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'read',
        ]);

        $this->assertDatabaseMissing('department_user', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'write',
        ]);

        $this->assertEquals(1, DepartmentUser::where('user_id', $user->id)->count());
    }

    public function testUpdateDepartmentRolesWithEmptyArrayRemovesAllRoles(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $department = Department::factory()->create();

        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'read',
        ]);

        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'write',
        ]);

        $action = new UserUpdateDepartmentRolesAction();
        $action->execute($user, []);

        Event::assertDispatched(DepartmentRolesWithdrawnEvent::class);
        Event::assertNotDispatched(DepartmentRolesAssignedEvent::class);

        $this->assertDatabaseMissing('department_user', [
            'user_id' => $user->id,
        ]);
    }

    public function testUpdateDepartmentRolesWithNoChangesDoesNotFireEvents(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $department = Department::factory()->create();

        DepartmentUser::factory()->create([
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => 'read',
        ]);

        $existingRoles = [$department->id->toString() => ['read']];

        $action = new UserUpdateDepartmentRolesAction();
        $action->execute($user, $existingRoles);

        Event::assertNotDispatched(DepartmentRolesAssignedEvent::class);
        Event::assertNotDispatched(DepartmentRolesWithdrawnEvent::class);
    }
}
