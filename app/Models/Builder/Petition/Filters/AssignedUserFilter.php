<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Petition>
 */
class AssignedUserFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($value === 'none') {
            $query->doesntHave('assignments');

            return;
        }

        $query->whereHas('assignments', static function (Builder $assignmentsQuery) use ($value): void {
            $assignmentsQuery->where('user_id', $value);
        });
    }
}
