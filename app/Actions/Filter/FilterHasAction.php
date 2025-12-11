<?php

declare(strict_types=1);

namespace App\Actions\Filter;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;

final readonly class FilterHasAction
{
    public function execute(User $user, Department $department, string $filterableType): bool
    {
        return UserDepartmentFilter::query()->where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', $filterableType)
            ->exists();
    }
}
