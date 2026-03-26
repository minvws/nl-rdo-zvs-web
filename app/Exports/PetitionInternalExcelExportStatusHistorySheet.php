<?php

declare(strict_types=1);

namespace App\Exports;

use App\Facades\DisplayDate;
use App\Models\Builder\Petition\Filters\PetitionStatusHistoryDateRangeFilter;
use App\Models\Builder\Petition\Filters\PetitionTypeFilter;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\Models\PetitionStatusHistory;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Webmozart\Assert\Assert;

use function __;

/**
 * @implements WithMapping<PetitionStatusHistory>
 */
class PetitionInternalExcelExportStatusHistorySheet implements WithTitle, FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        private readonly ExportCriteria $criteria,
    ) {
    }

    public function title(): string
    {
        return 'Statussen';
    }

    /**
     * @return array<string>
     */
    public function headings(): array
    {
        return [
            __('petition.number'),
            __('petition.status'),
            __('petition.substatus'),
            __('general.date_and_timestamp'),
        ];
    }

    /**
     * @param PetitionStatusHistory $row
     *
     * @return array<array-key, mixed>
 */
    public function map(mixed $row): array
    {
        return [
            $row->petition->number,
            __('petition_status.' . $row->petitionStatus->status_group->value),
            $row->petitionStatus->status,
            DisplayDate::datetime($row->created_at),
        ];
    }

    /**
     * @return Builder<PetitionStatusHistory>
     */
    public function query(): Builder
    {
        $petitionQuery = QueryBuilder::for(Petition::class)
            ->allowedFilters(
                AllowedFilter::custom('dateRange', new PetitionStatusHistoryDateRangeFilter())
                    ->default($this->criteria->dateRange),
                AllowedFilter::custom('petitionType', new PetitionTypeFilter())
                    ->default($this->criteria->petitionType->id),
            )
            ->when($this->criteria->petitionCategory instanceof PetitionCategory, function (Builder $query): void {
                Assert::isInstanceOf($this->criteria->petitionCategory, PetitionCategory::class);
                $query->where('petition_category_id', $this->criteria->petitionCategory->id);
            })
            ->getEloquentBuilder()
            ->notArchived();

        return PetitionStatusHistory::query()
            ->orderBy('created_at', 'desc')
            ->whereHas('petition', static function ($query) use ($petitionQuery): void {
                $query->whereIn('id', $petitionQuery->pluck('id'));
            });
    }
}
