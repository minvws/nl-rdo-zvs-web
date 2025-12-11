<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionBeroepExcelExport;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionBeroepExcelExportTest extends FeatureTestCase
{
    use ExportTestDataMapper;

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider1')]
    public function testMap(array $data): void
    {
        $row = $this->mapDataToPetition($data);
        $petitionBeroepExcelExport = $this->makePetitionBeroepExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            '2022-01-01',
            'Deels gegrond, Niet gegrond',
        ];

        $this->assertEquals($expectedResult, $petitionBeroepExcelExport->map($row));
    }

    public function testHeadings(): void
    {
        $petitionBeroepExcelExport = $this->makePetitionBeroepExcelExport();

        $expectedResult = [
            __('exports.reference'),
            __('exports.subject'),
            __('exports.date_of_entry'),
            __('exports.date_withdrawn'),
            __('exports.date_ruling'),
            __('exports.decision'),
        ];

        $this->assertEquals($expectedResult, $petitionBeroepExcelExport->headings());
    }

    public function makePetitionBeroepExcelExport(): PetitionBeroepExcelExport
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionBeroepExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $endDate),
            ),
        );
    }
}
