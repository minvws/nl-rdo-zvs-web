<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Enums\CustomDateLabel;
use App\Enums\ResultType;
use App\Models\Builder\Petition\Filters\PetitionStatusHistoryDateRangeFilter;
use App\Models\Builder\Petition\Filters\PetitionTypeFilter;
use App\Models\CustomPetitionProperty;
use App\Models\Petition;
use App\Models\PetitionCategory;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;
use Webmozart\Assert\Assert;

use function collect;

/**
 * @implements WithMapping<mixed>
 */
abstract class PetitionAbstractExcelExport implements WithTitle, WithMapping, WithHeadings, FromQuery, PetitionExportInterface
{
    use Exportable;

    public function __construct(
        protected readonly string $worksheetName,
        protected readonly ExportCriteria $criteria,
    ) {
    }

    public function title(): string
    {
        return $this->worksheetName;
    }

    public function writeToDisk(string $path, string $disk): void
    {
        $this->store($path, $disk);
    }

    /**
     * @return Builder<Petition>
     */
    public function query(): Builder
    {
        return QueryBuilder::for(Petition::class)
            ->allowedFilters(
                AllowedFilter::custom('dateRange', new PetitionStatusHistoryDateRangeFilter())
                    ->default($this->criteria->dateRange),
                AllowedFilter::custom('petitionType', new PetitionTypeFilter())
                    ->default($this->criteria->petitionType->id),
            )
            ->getEloquentBuilder()
            ->with(['customDates']) // Eager load the custom dates relationship
            ->notArchived()
            ->when($this->criteria->petitionCategory instanceof PetitionCategory, function (Builder $query): void {
                Assert::isInstanceOf($this->criteria->petitionCategory, PetitionCategory::class);
                $query->where('petition_category_id', $this->criteria->petitionCategory->id);
            });
    }

    /**
     * @param array<string, string> $options
     */
    protected function formatMatchingCustomOptions(CustomPetitionPropertyCollection $customPetitionProperties, array $options): string
    {
        $names = $customPetitionProperties->map(static fn(CustomPetitionProperty $value): string => $value->name);

        return collect($options)
            ->only($names->all())
            ->join(', ');
    }

    protected function formatCustomDateValueByLabel(
        PetitionCustomDateCollection $customDates,
        CustomDateLabel $customDateLabel,
    ): ?float {
        return ExcelDate::fromCalendarDate($customDates->getByLabel($customDateLabel)?->date);
    }

    protected function formatDate(?CalendarDate $date): ?float
    {
        return ExcelDate::fromCalendarDate($date);
    }

    protected function dateWithdrawn(Petition $petition, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return $this->formatDate($petition->petitionEvents->finalResult(ResultType::WITHDRAWN)?->date);
        }

        return $this->formatCustomDateValueByLabel($petition->customDates, CustomDateLabel::DATE_WITHDRAWN);
    }

    protected function dateDecisionOnAppeal(Petition $petition, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return $this->formatDate($petition->petitionEvents->finalResult(ResultType::FINAL_DECISION)?->date);
        }

        return $this->formatCustomDateValueByLabel($petition->customDates, CustomDateLabel::DATE_DECISION_ON_APPEAL);
    }

    protected function dateSettlementWithoutDecision(Petition $petition, bool $termEngineConverted): ?float
    {
        $finalResult = $termEngineConverted
            ? $petition->petitionEvents->finalResultWithoutDecision()
            : null;

        if ($finalResult !== null) {
            return $this->formatDate($finalResult->date);
        }

        return $this->formatCustomDateValueByLabel($petition->customDates, CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION);
    }

    protected function dateOfDecision(Petition $petition, bool $termEngineConverted): ?float
    {
        if ($termEngineConverted) {
            return $this->formatDate($petition->petitionEvents->finalResult(ResultType::FINAL_DECISION)?->date);
        }

        return $this->dateOfLatestDecision($petition);
    }

    protected function dateOfLatestDecision(Petition $petition): ?float
    {
        return $this->formatDate($petition->decisions->sortByDesc('date')->first()?->date);
    }
}
