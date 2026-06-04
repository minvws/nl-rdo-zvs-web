<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\AssignmentRole;
use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\User;
use App\QueryBuilders\UserQueryBuilder;
use Tests\Feature\FeatureTestCase;

class UserQueryBuilderTest extends FeatureTestCase
{
    public function testGetUsersWithWriteAccessOnDepartment(): void
    {
        $department = Department::factory()->create();
        $userWithWriteAccess = User::factory()->create();
        $userWithReadAccess = User::factory()->create();
        $userWithoutAccess = User::factory()->create();


        $department->users()->attach($userWithWriteAccess, ['role' => DepartmentRole::WRITE->value]);
        $department->users()->attach($userWithReadAccess, ['role' => DepartmentRole::READ->value]);

        /** @var UserQueryBuilder $queryBuilder */
        $queryBuilder = User::query();

        $users = $queryBuilder->getUsersWithWriteAccessOnDepartment($department)->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users->contains($userWithWriteAccess->id));
        $this->assertFalse($users->contains($userWithReadAccess->id));
        $this->assertFalse($users->contains($userWithoutAccess->id));
    }

    public function testIsAssigneeWithinDepartment(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();
        $assignedUser = User::factory()->create();
        $notAssignedUserInDepartmentA = User::factory()->create();
        $petitionA = Petition::factory()->recycle($departmentA)->create();
        $petitionB = Petition::factory()->recycle($departmentB)->create();
        PetitionAssignment::factory()->create(
            ['petition_id' => $petitionA->id, 'user_id' => $assignedUser->id, 'assignment_role' => AssignmentRole::PRIMARY],
        );
        PetitionAssignment::factory()->create(
            ['petition_id' => $petitionB->id, 'user_id' => $notAssignedUserInDepartmentA->id, 'assignment_role' => AssignmentRole::PRIMARY],
        );

        /** @var UserQueryBuilder $queryBuilder */
        $queryBuilder = User::query();
        $assignedUsers = $queryBuilder->isAssignee($petitionA->department)->get();

        $this->assertCount(1, $assignedUsers);
        $this->assertTrue($assignedUsers->contains($assignedUser->id));
        $this->assertFalse($assignedUsers->contains($notAssignedUserInDepartmentA->id));
    }
}
