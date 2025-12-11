<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Sorts;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class ReferenceSort implements Sort
{
    /**
     * @param Builder<Decision> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy('reference', $descending ? 'desc' : 'asc');
    }
}
