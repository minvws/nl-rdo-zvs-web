<?php

declare(strict_types=1);

namespace App\Services\Authorisation;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

use function auth;
use function sprintf;

class ActiveDepartmentService
{
    public function getActiveDepartment(): ?Department
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return null;
        }

        if ($user->last_visited_department_id !== null) {
            return $user->lastVisitedDepartment;
        }

        return Cache::store('array')->remember(
            sprintf('active_department_%s', $user->id),
            null,
            function () use ($user): ?Department {
                return $this->determineActiveDepartment($user);
            },
        );
    }

    public function determineActiveDepartment(User $user): ?Department
    {
        $lastVisitedDepartment = $user->lastVisitedDepartment;
        if ($lastVisitedDepartment instanceof Department) {
            return $lastVisitedDepartment;
        }

        $departments = $user->departments()->orderBy('slug')->get();

        $departmentsWithWritePermission = $departments->filter(static function (Department $department): bool {
            /** @var DepartmentUser $pivot */
            $pivot = $department->pivot;

            return $pivot->role === DepartmentRole::WRITE;
        });

        if (!$departmentsWithWritePermission->isEmpty()) {
            return $departmentsWithWritePermission->first();
        }

        $departmentsWithReadPermission = $departments->filter(static function (Department $department): bool {
            /** @var DepartmentUser $pivot */
            $pivot = $department->pivot;

            return $pivot->role === DepartmentRole::READ;
        });

        if (!$departmentsWithReadPermission->isEmpty()) {
            return $departmentsWithReadPermission->first();
        }

        return null;
    }

    public function hasActiveDepartment(): bool
    {
        $user = auth()->user();

        if (!$user instanceof User) {
            return false;
        }

        return $user->last_visited_department_id !== null;
    }
}
