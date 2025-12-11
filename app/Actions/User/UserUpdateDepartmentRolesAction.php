<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Events\DepartmentRolesAssignedEvent;
use App\Events\DepartmentRolesWithdrawnEvent;
use App\Models\DepartmentUser;
use App\Models\User;

use function collect;
use function event;

class UserUpdateDepartmentRolesAction
{
    /**
     * @param array<string, list<string>> $departmentRoles
     */
    public function execute(User $user, array $departmentRoles): void
    {
        $beforeRoles = collect(DepartmentUser::query()->where('user_id', $user->id)->get()->all());

        DepartmentUser::query()->where('user_id', $user->id)->delete();

        foreach ($departmentRoles as $departmentId => $departmentSpecificRoles) {
            foreach ($departmentSpecificRoles as $departmentRole) {
                DepartmentUser::query()->create([
                    'user_id' => $user->id,
                    'department_id' => $departmentId,
                    'role' => $departmentRole,
                ]);
            }
        }

        $afterRoles = collect(DepartmentUser::query()->where('user_id', $user->id)->get()->all());

        $deletedRoles = $beforeRoles->diff($afterRoles);

        if ($deletedRoles->isNotEmpty()) {
            event(new DepartmentRolesWithdrawnEvent($user, $deletedRoles));
        }

        $addedRoles = $afterRoles->diff($beforeRoles);
        if ($addedRoles->isNotEmpty()) {
            event(new DepartmentRolesAssignedEvent($user, $addedRoles));
        }
    }
}
