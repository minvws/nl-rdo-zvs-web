<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @method static PetitionStatusQueryBuilder query()
 *
 * @template-extends Builder<PetitionStatus>
 */
class PetitionStatusQueryBuilder extends Builder
{
    public function usedByDepartment(Department $department): PetitionStatusQueryBuilder
    {
        return $this->select('status')
            ->whereIn(
                'id',
                Petition::query()
                    ->where('department_id', $department->id)
                    ->whereNotNull('petition_status_id')
                    ->pluck('petition_status_id'),
            )
            ->orderBy('status')
            ->groupBy('status');
    }
}
