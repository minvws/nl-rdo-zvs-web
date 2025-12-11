<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionWooVerzoekExcelExport;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionWooVerzoekExcelExportTest extends FeatureTestCase
{
    use ExportTestDataMapper;

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider1')]
    public function testMapVerdaging(array $data): void
    {
        $row = $this->mapDataToPetition($data);
        $petitionWooVerzoekExcelExport = $this->makePetitionWooVerzoekExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            'Verzoek betrof bij nader inzien burgervraag',
            null,
            '2023-01-01',
            0,
            __('exports.false'),
            null,
            __('exports.true'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider2')]
    public function testMapInOverlegMetVerzoeker(array $data): void
    {
        $row = $this->mapDataToPetition($data);
        $petitionWooVerzoekExcelExport = $this->makePetitionWooVerzoekExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            null,
            null,
            0,
            __('exports.true'),
            '2023-01-02',
            __('exports.false'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->map($row));
    }

    public function testHeadings(): void
    {
        $petitionWooVerzoekExcelExport = $this->makePetitionWooVerzoekExcelExport();

        $expectedResult = [
            __('exports.reference'),
            __('exports.subject'),
            __('exports.date_of_receipt'),
            __('exports.reason_for_settlement_without_decision'),
            __('exports.date_settlement_without_decision'),
            __('exports.date_decision'),
            __('exports.number_of_days_of_suspension'),
            __('exports.in_consultation_with_applicant'),
            __('exports.date_appointment_with_applicant'),
            __('exports.adjournment'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->headings());
    }

    public function makePetitionWooVerzoekExcelExport(): PetitionWooVerzoekExcelExport
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionWooVerzoekExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $endDate),
            ),
        );
    }
}
