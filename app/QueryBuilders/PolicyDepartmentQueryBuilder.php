<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\PolicyDepartment;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static PolicyDepartmentQueryBuilder query()
 * @method static PolicyDepartmentQueryBuilder active()
 *
 * @template-extends Builder<PolicyDepartment>
 */
class PolicyDepartmentQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }
}
