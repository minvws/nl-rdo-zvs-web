<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Enums\AssignmentRole;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class AssignedUserSort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $direction = $descending ? 'desc' : 'asc';

        $query->join('petition_assignments', 'petitions.id', '=', 'petition_assignments.petition_id')
            ->join('users', 'users.id', '=', 'petition_assignments.user_id')
            ->select('petitions.*')
            ->where('petition_assignments.assignment_role', AssignmentRole::PRIMARY->value)
            ->orderBy('users.name', $direction);
    }
}
