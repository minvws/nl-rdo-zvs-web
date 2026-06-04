<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Filters;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Decision>
 */
class TeamFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereRelation('team', 'id', $value);
    }
}
