<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\Models\PetitionType;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

class PetitionTypeSort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            PetitionType::query()->select('name')->whereColumn('id', 'petition_type_id'),
            $descending ? 'desc' : 'asc',
        );
    }
}
