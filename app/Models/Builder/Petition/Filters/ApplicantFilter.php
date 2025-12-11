<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Petition>
 */
class ApplicantFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $query->whereRelation('applicant', 'type', $value);
    }
}
