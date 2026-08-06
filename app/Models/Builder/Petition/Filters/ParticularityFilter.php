<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

/**
 * @implements Filter<Petition>
 */
class ParticularityFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::string($value);

        $query->whereParticularity($value);
    }
}
