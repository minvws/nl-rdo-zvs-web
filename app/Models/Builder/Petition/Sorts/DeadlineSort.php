<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;

use function sprintf;

/**
 * @implements Sort<Petition>
 */
class DeadlineSort implements Sort
{
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        $query->orderByRaw(
            sprintf('COALESCE(deadline_override_at, deadline_at) %s', $descending ? 'desc' : 'asc'),
        );
    }
}
