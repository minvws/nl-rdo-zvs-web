<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExcelDate;
use App\Exports\ExportCriteria;
use App\Exports\PetitionBeroepExcelExport;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
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
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')),
            null,
            ExcelDate::fromCalendarDate(CalendarDate::create('2022-01-01')),
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

    public function testColumnFormats(): void
    {
        $this->assertSame([
            'C' => ExcelDate::DATE_FORMAT,
            'D' => ExcelDate::DATE_FORMAT,
            'E' => ExcelDate::DATE_FORMAT,
        ], $this->makePetitionBeroepExcelExport()->columnFormats());
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
