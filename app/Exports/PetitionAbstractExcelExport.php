<?php

declare(strict_types=1);

namespace App\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Enums\CustomDateLabel;
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

use function array_key_exists;

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
        return $customPetitionProperties
            ->filter(static fn(CustomPetitionProperty $value, string $key): bool => array_key_exists($value->name, $options))
            ->map(static fn(CustomPetitionProperty $value, string $key) => $options[$value->name])
            ->join(', ');
    }

    protected function formatCustomDateValueByLabel(
        PetitionCustomDateCollection $customDates,
        CustomDateLabel $customDateLabel,
    ): ?string {
        $customDate = $customDates->getByLabel($customDateLabel);

        return $customDate?->date?->format('Y-m-d');
    }

    protected function getDateFromCalendarDateToDate(?CalendarDate $date): ?string
    {
        return $date?->format('Y-m-d');
    }

    protected function formatDate(?CalendarDate $date): ?string
    {
        return $this->getDateFromCalendarDateToDate($date);
    }
}
