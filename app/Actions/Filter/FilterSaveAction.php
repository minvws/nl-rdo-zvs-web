<?php

declare(strict_types=1);

namespace App\Actions\Filter;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;

final readonly class FilterSaveAction
{
    public function __construct(
        private FilterClearAction $filterClearAction,
    ) {
    }

    /**
     * @param array<mixed> $filters
     */
    public function execute(User $user, Department $department, string $filterableType, array $filters): void
    {
        if ($filters === []) {
            $this->filterClearAction->execute($user, $department, $filterableType);

            return;
        }

        UserDepartmentFilter::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'department_id' => $department->id,
                'filterable_type' => $filterableType,
            ],
            [
                'filter_data' => $filters,
            ],
        );
    }
}
