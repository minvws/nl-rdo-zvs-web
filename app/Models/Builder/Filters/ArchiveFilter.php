<?php

declare(strict_types=1);

namespace App\Models\Builder\Filters;

use App\Enums\ArchiveFilter as ArchiveFilterEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\Filters\Filter;
use Webmozart\Assert\Assert;

/**
 * @implements Filter<Model>
 */
class ArchiveFilter implements Filter
{
    public function __invoke(Builder $query, mixed $value, string $property): void
    {
        Assert::nullOrStringNotEmpty($value);

        $filterValue = ArchiveFilterEnum::tryFrom((string) $value);

        match ($filterValue) {
            ArchiveFilterEnum::SHOW_ARCHIVED => $query->whereNotNull('archived_at'),
            ArchiveFilterEnum::SHOW_ALL => null,
            default => $query->whereNull('archived_at'),
        };
    }
}
