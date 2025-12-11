<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class PenaltyToDateSort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        // this will only work if the query already has the penalty_to_date selected (see PetitionQueryBuilder)
        $query->orderBy($property, $descending ? 'desc' : 'asc')
            ->orderBy('id', 'asc');
    }
}
