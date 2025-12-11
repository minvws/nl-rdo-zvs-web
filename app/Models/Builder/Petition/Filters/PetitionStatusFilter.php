<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Petition>
 */
class PetitionStatusFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereRelation('petitionStatus', 'status', $value);
    }
}
