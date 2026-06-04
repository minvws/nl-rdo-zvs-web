<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\Authorization\DepartmentRole;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static UserQueryBuilder query()
 * @method static UserQueryBuilder active()
 *
 * @template-extends Builder<User>
 */
class UserQueryBuilder extends Builder
{
    public function getUsersWithWriteAccessOnDepartment(Department $department): static
    {
        return $this->whereHas('departments', static function (Builder $query) use ($department): void {
            $query->where('departments.id', $department->id)
                ->where('role', DepartmentRole::WRITE->value);
        });
    }

    public function isAssignee(Department $department): static
    {
        return $this->whereHas('petitionAssignments', static function (Builder $query) use ($department): void {
            $query->whereHas('petition', static function (Builder $petitionQuery) use ($department): void {
                $petitionQuery->where('department_id', $department->id);
            });
        });
    }

    public function active(): static
    {
        return $this->where('active', true);
    }
}
