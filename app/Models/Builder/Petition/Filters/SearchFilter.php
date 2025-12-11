<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition\Filters;

use App\Models\Petition;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Filters\Filter;

use function collect;
use function explode;
use function is_string;

/**
 * @implements Filter<Petition>
 */
class SearchFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        $terms = $this->prepareInputValues($value);

        foreach ($terms as $searchTerm) {
            $query->where(static function (Builder $subQuery) use ($searchTerm): void {
                $subQuery->orWhere('number', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhere('name', 'ILIKE', '%' . $searchTerm . '%');
                $subQuery->orWhereHas('applicant', self::buildSearchQuery($searchTerm));
                $subQuery->orWhereHas('representative', self::buildSearchQuery($searchTerm));
                $subQuery->orWhereHas('contacts', static function (Builder $pivotQuery) use ($searchTerm): void {
                    $pivotQuery->where('reference', 'ILIKE', '%' . $searchTerm . '%');
                });
            });
        }
    }

    private static function buildSearchQuery(string $searchTerm): Closure
    {
        return static function (Builder $query) use ($searchTerm): void {
            $query->where('last_name', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhere('email_address', 'ILIKE', '%' . $searchTerm . '%')
                ->orWhere('organisation_name', 'ILIKE', '%' . $searchTerm . '%');
        };
    }

    /**
     * @return array<string>
     */
    private function prepareInputValues(mixed $value): array
    {
        return collect((array) $value)
            ->filter(static fn($item): bool => is_string($item))
            ->flatMap(static fn($item): array => explode(' ', (string) $item))
            ->reject(static fn($item) => Str::of($item)->trim()->isEmpty())
            ->all();
    }
}
