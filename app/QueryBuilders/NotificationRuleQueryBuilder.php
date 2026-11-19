<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Department;
use App\Models\NotificationRule;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static NotificationRuleQueryBuilder query()
 * @method static NotificationRuleQueryBuilder active()
 * @method static NotificationRuleQueryBuilder forDepartment(Department $department)
 *
 * @template-extends Builder<NotificationRule>
 */
class NotificationRuleQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('active', true);
    }

    public function forDepartment(Department $department): static
    {
        return $this->where('department_id', $department->id);
    }
}
