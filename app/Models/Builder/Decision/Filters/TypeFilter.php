<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Filters;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

/**
 * @implements Filter<Decision>
 */
class TypeFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::string($value);

        $query->where('type', $value);
    }
}
