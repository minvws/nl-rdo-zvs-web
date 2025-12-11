<?php

declare(strict_types=1);

namespace App\Actions\Filter;

use App\Models\Department;
use App\Models\User;
use App\Models\UserDepartmentFilter;

use function collect;
use function in_array;

final readonly class FilterGetAction
{
    public function __construct(
        private FilterClearAction $filterClearAction,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(User $user, Department $department, string $filterableType): array
    {
        $filter = UserDepartmentFilter::query()
            ->where('user_id', $user->id)
            ->where('department_id', $department->id)
            ->where('filterable_type', $filterableType)
            ->first();

        if ($filter === null) {
            return [];
        }

        $cleanedFilters = collect($filter->filter_data)
            ->reject(static fn($value): bool => in_array($value, [null, '', 'null'], true))
            ->all();

        // If all filters are invalid, clear them from the database
        if (empty($cleanedFilters)) {
            $this->filterClearAction->execute($user, $department, $filterableType);

            return [];
        }

        return $cleanedFilters;
    }
}
