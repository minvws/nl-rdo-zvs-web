<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\Models\PetitionStatus;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

/**
 * @implements Sort<Petition>
 */
class StatusSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderBy(
            PetitionStatus::query()->select('order')->whereColumn('id', 'petition_status_id'),
            $descending ? 'desc' : 'asc',
        );
    }
}
