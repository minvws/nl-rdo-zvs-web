<?php

declare(strict_types=1);

namespace App\Collections;

use App\Models\PolicyDepartment;
use Illuminate\Database\Eloquent\Collection;

/**
 * @extends Collection<int, PolicyDepartment>
 */
class PolicyDepartmentCollection extends Collection
{
    public function toString(): string
    {
        return $this->map(static function (PolicyDepartment $policyDepartment): string {
            return $policyDepartment->name;
        })->join(', ');
    }
}
