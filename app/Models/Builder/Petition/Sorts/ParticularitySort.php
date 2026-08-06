<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Sorts;

use App\Models\Petition;
use App\QueryBuilders\PetitionQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Sorts\Sort;
use Webmozart\Assert\Assert;

class ParticularitySort implements Sort
{
    /**
     * @param Builder<Petition> $query
     */
    public function __invoke(Builder $query, bool $descending, string $property): void
    {
        Assert::isInstanceOf($query, PetitionQueryBuilder::class);

        $query->withParticularitySortKey()
            ->orderByRaw('particularity_sort_key ' . ($descending ? 'desc' : 'asc') . ' nulls last')
            ->orderBy('id', 'asc');
    }
}
