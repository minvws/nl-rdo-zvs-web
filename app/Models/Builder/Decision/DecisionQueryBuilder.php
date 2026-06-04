<?php

declare(strict_types=1);

namespace App\Models\Builder\Decision;

use App\Enums\DecisionCriteria;
use App\Models\Builder\Decision\Filters\ProcessingStepsInProgressFilter;
use App\Models\Builder\Decision\Filters\SearchFilter;
use App\Models\Builder\Decision\Filters\TeamFilter;
use App\Models\Builder\Decision\Filters\TypeFilter;
use App\Models\Builder\Decision\Sorts\DateSort;
use App\Models\Builder\Decision\Sorts\DeadlineSort;
use App\Models\Builder\Decision\Sorts\NameSort;
use App\Models\Builder\Decision\Sorts\ProgressSort;
use App\Models\Builder\Decision\Sorts\ReferenceSort;
use App\Models\Builder\Filters\ArchiveFilter;
use App\Models\Decision;
use App\QueryBuilders\DecisionQueryBuilder as CustomDecisionQueryBuilder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Webmozart\Assert\Assert;

readonly class DecisionQueryBuilder
{
    public static function make(?Request $request = null): CustomDecisionQueryBuilder
    {
        $builder = QueryBuilder::for(Decision::class, $request)
            ->allowedFilters(...self::createAllowedFilters())
            ->allowedSorts(...self::createAllowedSorts())
            ->defaultSort('-created_at', '-id')
            ->getEloquentBuilder()
            ->with([
                'processingSteps',
                'team:id,name',
            ]);

        Assert::isInstanceOf($builder, CustomDecisionQueryBuilder::class);

        return $builder;
    }

     /**
      * @return array<AllowedFilter>
      */
    private static function createAllowedFilters(): array
    {
        return [
            AllowedFilter::custom(DecisionCriteria::ARCHIVE->value, new ArchiveFilter()),
            AllowedFilter::custom(DecisionCriteria::SEARCH->value, new SearchFilter()),
            AllowedFilter::custom(DecisionCriteria::TEAM->value, new TeamFilter()),
            AllowedFilter::custom(DecisionCriteria::TYPE->value, new TypeFilter()),
            AllowedFilter::custom(DecisionCriteria::PROCESSING_STEPS_IN_PROGRESS->value, new ProcessingStepsInProgressFilter()),
        ];
    }

    /**
     * @return array<AllowedSort>
     */
    private static function createAllowedSorts(): array
    {
        return [
            AllowedSort::custom(DecisionCriteria::NAME->value, new NameSort()),
            AllowedSort::custom(DecisionCriteria::DATE->value, new DateSort()),
            AllowedSort::custom(DecisionCriteria::REFERENCE->value, new ReferenceSort()),
            AllowedSort::custom(DecisionCriteria::DEADLINE->value, new DeadlineSort()),
            AllowedSort::custom(DecisionCriteria::PROGRESS->value, new ProgressSort()),
        ];
    }
}
