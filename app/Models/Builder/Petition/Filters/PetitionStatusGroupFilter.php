<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Enums\StatusGroup;
use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;

/**
 * @implements Filter<Petition>
 */
class PetitionStatusGroupFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        if ($value === StatusGroup::NOT_CLOSED->value) {
            $query->whereHas('petitionStatus', static fn (Builder $q) => $q->where('status_group', '!=', StatusGroup::CLOSED->value));
        } else {
            $query->whereRelation('petitionStatus', 'status_group', $value);
        }
    }
}
