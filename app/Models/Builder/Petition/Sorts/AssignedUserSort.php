<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class AssignedUserSort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            User::query()->select('name')->whereColumn('id', 'assigned_to'),
            $descending ? 'desc' : 'asc',
        );
    }
}
