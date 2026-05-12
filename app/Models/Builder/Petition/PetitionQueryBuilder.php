<?php

declare(strict_types=1);

namespace App\Models\Builder\Petition;

use App\Enums\PetitionCriteria;
use App\Models\Builder\Filters\ArchiveFilter;
use App\Models\Builder\Petition\Filters\ApplicantFilter;
use App\Models\Builder\Petition\Filters\AssignedUserFilter;
use App\Models\Builder\Petition\Filters\CategoryFilter;
use App\Models\Builder\Petition\Filters\CustomPropertyFilter;
use App\Models\Builder\Petition\Filters\PetitionStatusFilter;
use App\Models\Builder\Petition\Filters\PetitionStatusGroupFilter;
use App\Models\Builder\Petition\Filters\PetitionTypeFilter;
use App\Models\Builder\Petition\Filters\PolicyDepartmentFilter;
use App\Models\Builder\Petition\Filters\SearchFilter;
use App\Models\Builder\Petition\Sorts\ApplicantSort;
use App\Models\Builder\Petition\Sorts\AssignedUserSort;
use App\Models\Builder\Petition\Sorts\CategorySort;
use App\Models\Builder\Petition\Sorts\PenaltyToDateSort;
use App\Models\Builder\Petition\Sorts\PetitionTypeSort;
use App\Models\Builder\Petition\Sorts\StatusGroupSort;
use App\Models\Builder\Petition\Sorts\StatusSort;
use App\Models\Builder\Petition\Sorts\SumOfPenaltiesPerDateSort;
use App\Models\Petition;
use App\QueryBuilders\PetitionQueryBuilder as CustomPetitionQueryBuilder;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Webmozart\Assert\Assert;

readonly class PetitionQueryBuilder
{
    public static function make(?Request $request = null): CustomPetitionQueryBuilder
    {
        $builder = QueryBuilder::for(Petition::class, $request)
            ->allowedFilters(...self::createAllowedFilters())
            ->allowedSorts(...self::createAllowedSorts())
            ->defaultSort('-created_at', '-id')
            ->getEloquentBuilder()
            ->with([
                'petitionType',
                'applicant',
                'policyDepartments:id,name',
                'assignedUser:id,name',
                'petitionStatus',
                'petitionCategory:id,name',
                'petitionTerms',
                'relatedPetitions.petitionType:id,particularity_label',
            ]);

        Assert::isInstanceOf($builder, CustomPetitionQueryBuilder::class);

        return $builder;
    }

    /**
     * @return array<AllowedFilter>
     */
    private static function createAllowedFilters(): array
    {
        return [
            AllowedFilter::custom(PetitionCriteria::APPLICANT->value, new ApplicantFilter()),
            AllowedFilter::custom(PetitionCriteria::ARCHIVE->value, new ArchiveFilter()),
            AllowedFilter::custom(PetitionCriteria::ASSIGNED_USER->value, new AssignedUserFilter()),
            AllowedFilter::custom(PetitionCriteria::CATEGORY->value, new CategoryFilter()),
            AllowedFilter::custom(PetitionCriteria::CUSTOM_PROPERTY->value, new CustomPropertyFilter()),
            AllowedFilter::custom(PetitionCriteria::PETITION_TYPE->value, new PetitionTypeFilter()),
            AllowedFilter::custom(PetitionCriteria::POLICY_DEPARTMENT->value, new PolicyDepartmentFilter()),
            AllowedFilter::custom(PetitionCriteria::SEARCH->value, new SearchFilter()),
            AllowedFilter::custom(PetitionCriteria::STATUS->value, new PetitionStatusFilter()),
            AllowedFilter::custom(PetitionCriteria::STATUS_GROUP->value, new PetitionStatusGroupFilter()),
        ];
    }

    /**
     * @return array<string|AllowedSort>
     */
    private static function createAllowedSorts(): array
    {
        return [
            PetitionCriteria::NUMBER->value,
            PetitionCriteria::NAME->value,
            PetitionCriteria::DEADLINE_AT->value,
            AllowedSort::custom(PetitionCriteria::APPLICANT->value, new ApplicantSort()),
            AllowedSort::custom(PetitionCriteria::ASSIGNED_USER->value, new AssignedUserSort()),
            AllowedSort::custom(PetitionCriteria::CATEGORY->value, new CategorySort()),
            AllowedSort::custom(PetitionCriteria::PENALTY_TO_DATE->value, new PenaltyToDateSort()),
            AllowedSort::custom(PetitionCriteria::PETITION_TYPE->value, new PetitionTypeSort()),
            AllowedSort::custom(PetitionCriteria::STATUS->value, new StatusSort()),
            AllowedSort::custom(PetitionCriteria::STATUS_GROUP->value, new StatusGroupSort()),
            AllowedSort::custom(PetitionCriteria::SUM_OF_PENALTIES_PER_DATE->value, new SumOfPenaltiesPerDateSort()),
        ];
    }
}
