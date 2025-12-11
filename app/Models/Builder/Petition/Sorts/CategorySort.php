<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class CategorySort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            PetitionCategory::query()->select('name')->whereColumn('id', 'petition_category_id'),
            $descending ? 'desc' : 'asc',
        );
    }
}
