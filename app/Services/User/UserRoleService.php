<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Enums\Authorization\DepartmentRole;
use App\Models\DepartmentUser;
use Ramsey\Uuid\UuidInterface;

readonly class UserRoleService
{
    /**
     * Returns an array for each department where the user has a role. The array is keyed by the id
     * of the department, and contains an array of department-roles.
     *
     * @return array<string, array<DepartmentRole>>
     */
    public function getDepartmentRoles(UuidInterface $id): array
    {
        /** @var array<string, array<DepartmentRole>> $departmentRoles */
        $departmentRoles = DepartmentUser::query()->where('user_id', $id)
            ->get()
            ->mapToGroups(static function (object $departmentRole): array {
                return [$departmentRole->department_id->toString() => $departmentRole->role];
            })->toArray();

        return $departmentRoles;
    }
}
