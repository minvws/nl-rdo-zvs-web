<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision\Filters;

use App\Models\Decision;
use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

use function explode;

/**
 * @implements Filter<Decision>
 */
class SearchFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::string($value);
        $terms = explode(' ', $value);

        foreach ($terms as $searchTerm) {
            $query->where(static function (Builder $subQuery) use ($searchTerm): void {
                $subQuery->orWhere('name', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhere('reference', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhere('reviewbatch', 'ILIKE', '%' . $searchTerm . '%');
            });
        }
    }
}
