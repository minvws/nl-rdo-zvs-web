<?php

declare(strict_types=1);

namespace App\QueryBuilders;

use App\Enums\TermType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionTerm;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;

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
        $today = CalendarDate::today()->toDateString();

        return $this->addSelect([
            'sum_of_penalties_per_date' => PetitionTerm::query()
                ->selectRaw('COALESCE(SUM(penalty_amount_in_euros), 0)')
                ->whereColumn('petition_id', 'petitions.id')
                ->where('type', TermType::PENALTY->value)
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today),
        ]);
    }

    public function withPenaltyToDate(): PetitionQueryBuilder
    {
        $today = CalendarDate::today()->toDateString();

        return $this->addSelect([
            'penalty_to_date' => PetitionTerm::query()
                ->selectRaw('
                    COALESCE(SUM(
                        CASE 
                            WHEN start_date <= ?::date THEN
                                CASE 
                                    WHEN (?::date - start_date + 1) > duration_in_days THEN 
                                        duration_in_days * penalty_amount_in_euros
                                    ELSE 
                                        (?::date - start_date + 1) * penalty_amount_in_euros
                                END
                            ELSE 0
                        END
                    ), 0)
                ', [$today, $today, $today])
                ->whereColumn('petition_id', 'petitions.id')
                ->where('type', TermType::PENALTY->value),
        ]);
    }
}
