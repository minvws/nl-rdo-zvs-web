<?php

declare(strict_types=1);

namespace App\Actions\PetitionExport;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Factories\Export\PetitionExportFactory;
use App\Models\Department;
use App\Models\PetitionCategory;
use App\Models\PetitionExport;
use App\Models\PetitionType;
use App\Services\Petition\Export\PetitionExportException;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Container\Attributes\Config;
use TypeError;
use ValueError;
use Webmozart\Assert\Assert;

use function json_encode;
use function sprintf;

class PetitionExportCreateAction
{
    public function __construct(
        private readonly PetitionExportFactory $petitionExportFactory,
        #[Config('filesystems.exports')]
        private readonly string $disk,
    ) {
    }

    /**
     * @param array<string, mixed> $attributes
     *
     * @throws PetitionExportException
     * @throws TypeError
     * @throws ValueError
     */
    public function execute(Department $department, array $attributes): void
    {
        Assert::keyExists($attributes, 'petition_type_id');
        Assert::keyExists($attributes, 'date_from');
        Assert::keyExists($attributes, 'date_to');
        Assert::keyExists($attributes, 'export_type');
        Assert::string($attributes['export_type']);
        Assert::string($attributes['petition_type_id']);
        Assert::uuid($attributes['petition_type_id']);
        Assert::string($attributes['date_from']);
        Assert::string($attributes['date_to']);

        $petitionTypeId = $attributes['petition_type_id'];
        $petitionCategoryId = $attributes['petition_category_id'] ?? null;
        $dateFrom = $attributes['date_from'];
        $dateTo = $attributes['date_to'];
        $exportType = ExportType::from($attributes['export_type']);

        if ($petitionCategoryId !== null) {
            Assert::string($petitionCategoryId);
            Assert::uuid($petitionCategoryId);
        }

        $petitionType = PetitionType::query()->findSole($petitionTypeId);
        $petitionCategory = PetitionCategory::query()->find($petitionCategoryId);

        $criteria = new ExportCriteria(
            $petitionType,
            $exportType,
            new DateRange(CalendarDate::create($dateFrom), CalendarDate::create($dateTo)),
            $petitionCategory,
        );

        $export = $this->petitionExportFactory->create($criteria);

        $exportModel = PetitionExport::query()->create([
            'department_id' => $department->id,
            'petition_type_id' => $petitionTypeId,
            'petition_category_id' => $petitionCategoryId,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'filters' => json_encode($attributes),
            'type' => $exportType,
            'disk' => $this->disk,
        ]);

        $export->writeToDisk(sprintf('%s.xlsx', $exportModel->id->toString()), $this->disk);
    }
}
