<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionGenericExcelExport;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionGenericExcelExportTest extends FeatureTestCase
{
    use ExportTestDataMapper;

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider1')]
    public function testMap(array $data): void
    {
        $row = $this->mapDataToPetition($data);
        $petitionGenericExcelExport = $this->makeGenericExcelExport();

        $expectedResult = [
            'number',
            'name',
        ];

        $this->assertEquals($expectedResult, $petitionGenericExcelExport->map($row));
    }

    public function testHeadings(): void
    {
        $petitionGenericExcelExport = $this->makeGenericExcelExport();

        $expectedResult = [
            __('exports.reference'),
            __('exports.subject'),
        ];

        $this->assertEquals($expectedResult, $petitionGenericExcelExport->headings());
    }

    public function makeGenericExcelExport(): PetitionGenericExcelExport
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionGenericExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $endDate),
            ),
        );
    }
}
