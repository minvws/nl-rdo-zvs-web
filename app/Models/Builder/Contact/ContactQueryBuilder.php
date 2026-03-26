<?php

declare(strict_types=1);

namespace App\Models\Builder\Contact;

use App\Enums\PetitionCriteria;
use App\Models\Builder\Contact\Filters\SearchFilter;
use App\Models\Contact;
use App\QueryBuilders\ContactQueryBuilder as CustomContactQueryBuilder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Webmozart\Assert\Assert;

readonly class ContactQueryBuilder
{
    public static function make(?Request $request = null): CustomContactQueryBuilder
    {
        $builder = QueryBuilder::for(Contact::class, $request)
            ->allowedFilters(...self::createAllowedFilters())
            ->allowedSorts(...self::createAllowedSorts())
            ->defaultSort('last_name', 'organisation_name', 'id')
            ->getEloquentBuilder();

        Assert::isInstanceOf($builder, CustomContactQueryBuilder::class);

        return $builder;
    }

    /**
     * @return array<AllowedFilter>
     */
    private static function createAllowedFilters(): array
    {
        return [
            AllowedFilter::custom(PetitionCriteria::SEARCH->value, new SearchFilter()),
        ];
    }

    /**
     * @return array<string|AllowedSort>
     */
    private static function createAllowedSorts(): array
    {
        return [];
    }
}
