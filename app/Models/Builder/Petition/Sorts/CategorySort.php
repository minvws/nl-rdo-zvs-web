<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Petition>
 */
class CategorySort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            PetitionCategory::query()->select('name')->whereColumn('id', 'petition_category_id'),
            $descending ? 'desc' : 'asc',
        );
    }
}
