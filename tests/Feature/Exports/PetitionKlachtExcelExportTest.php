<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionKlachtExcelExport;
use App\Models\Petition;
use App\Models\PetitionType;
use App\ValueObjects\DateRange;
use Mockery;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionKlachtExcelExportTest extends FeatureTestCase
{
    use ExportTestDataMapper;

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider1')]
    public function testMapWithSomeMatchingProperties(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionKlachtExcelExport = $this->makePetitionKlachtExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            '2022-01-01',
            'Binnen wettelijke termijn, Binnen afgesproken termijn',
            'Deels gegrond, Niet gegrond',
        ];

        $this->assertEquals($expectedResult, $petitionKlachtExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider2')]
    public function testMapWithNoneMatchingProperties(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionKlachtExcelExport = $this->makePetitionKlachtExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            '2022-01-01',
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionKlachtExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProviderExternal(PetitionExcelExport::class, 'excelExportDataProvider3')]
    public function testMapNoCustomDates(array $data): void
    {
        $row = $this->mockPetitionAsLegacy($this->mapDataToPetition($data));
        $petitionKlachtExcelExport = $this->makePetitionKlachtExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionKlachtExcelExport->map($row));
    }

    public function testHeadings(): void
    {
        $petitionKlachtExcelExport = $this->makePetitionKlachtExcelExport();

        $expectedResult = [
            __('exports.reference'),
            __('exports.subject'),
            __('exports.date_of_entry'),
            __('exports.date_decision'),
            __('exports.within_outside_term'),
            __('exports.decision'),
        ];

        $this->assertEquals($expectedResult, $petitionKlachtExcelExport->headings());
    }

    public function makePetitionKlachtExcelExport(): PetitionKlachtExcelExport
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionKlachtExcelExport(
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
