<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Sorts;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Decision>
 */
class ReferenceSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy('reference', $descending ? 'desc' : 'asc');
    }
}
