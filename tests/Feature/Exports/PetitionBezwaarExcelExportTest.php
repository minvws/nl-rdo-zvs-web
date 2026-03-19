<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionBezwaarExcelExport;
use App\Models\Petition;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;
use Mockery;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionBezwaarExcelExportTest extends FeatureTestCase
{
    use ExportTestDataMapper;

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider1')]
    public function testMapWithSomeMatchingProperties(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            '2022-01-01',
            'Binnen wettelijke termijn, Binnen afgesproken termijn',
            'Deels gegrond, Niet gegrond',
        ];

        $this->assertEquals($expectedResult, $petitionBezwaarExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider2')]
    public function testMapWithNoneMatchingProperties(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            '2022-01-01',
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionBezwaarExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider3')]
    public function testMapNoCustomDates(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            null,
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionBezwaarExcelExport->map($row));
    }

    public function testHeadings(): void
    {
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            __('exports.reference'),
            __('exports.subject'),
            __('exports.date_of_entry'),
            __('exports.date_withdrawn'),
            __('exports.date_decision'),
            __('exports.decision_within_or_outside_term'),
            __('exports.decision'),
        ];

        $this->assertEquals($expectedResult, $petitionBezwaarExcelExport->headings());
    }

    public function makePetitionBezwaarExcelExport(): PetitionBezwaarExcelExport
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionBezwaarExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $endDate),
            ),
        );
    }

    private function mockPetitionAsLegacy(Petition $petition): Petition
    {
        return Mockery::mock($petition)
            ->makePartial()
            ->shouldReceive('isTermEngineConverted')
            ->andReturn(false)
            ->getMock();
    }
}
