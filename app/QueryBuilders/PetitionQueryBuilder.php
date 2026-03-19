<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Models\Department;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @method static PetitionQueryBuilder query()
 *
 * @template-extends Builder<Petition>
 */
class PetitionQueryBuilder extends Builder
{
    public function notArchived(): PetitionQueryBuilder
    {
        return $this->whereNull('archived_at');
    }

    public function whereDepartment(Department $department): PetitionQueryBuilder
    {
        return $this->where('department_id', $department->id);
    }

    public function withSumOfPenaltiesPerDate(): PetitionQueryBuilder
    {
        return $this->addSelect([
            'petitions.*',
            DB::raw('legacy_term_penalty_today + igs_penalty_today + bnt_penalty_today as sum_of_penalties_per_date'),
        ]);
    }

    public function withPenaltyToDate(): PetitionQueryBuilder
    {
        return $this->addSelect([
            'petitions.*',
            DB::raw('legacy_term_forfeited + igs_forfeited + bnt_forfeited as penalty_to_date'),
        ]);
    }
}
