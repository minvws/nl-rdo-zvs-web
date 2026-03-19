<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Enums\ExportType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionBezwaarExcelExport;
use App\Models\CustomPetitionProperty;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function collect;

class PetitionBezwaarExcelExportV2Test extends FeatureTestCase
{
    /**
     * @param array<mixed> $data
     */
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedWithAllEvents(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEvents($data);
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            '2021-06-01',
            '2022-01-01',
            'Binnen wettelijke termijn, Binnen afgesproken termijn',
            'Deels gegrond, Niet gegrond',
        ];

        $this->assertEquals($expectedResult, $petitionBezwaarExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedWithoutWithdrawnEvent(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEventsWithoutWithdrawn($data);
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
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedWithoutAnyEvents(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEventsMinimal($data);
        $petitionBezwaarExcelExport = $this->makePetitionBezwaarExcelExport();

        $expectedResult = [
            'number',
            'name',
            null,
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

    /**
     * @return array<string, array<mixed>>
     */
    public static function excelExportDataProviderV2(): array
    {
        return [
            'test case' => [
                [
                    'number' => 'number',
                    'name' => 'name',
                ],
            ],
        ];
    }

    /**
     * @param array<mixed> $data
     */
    private function createPetitionWithTermEngineEvents(array $data): Petition
    {
        $events = collect([
            new PetitionEvent([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2021-01-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::WITHDRAWN,
                'date' => CalendarDate::create('2021-06-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::FINAL_DECISION,
                'date' => CalendarDate::create('2022-01-01'),
            ]),
        ]);

        return $this->buildPetitionWithEvents($data, $events, $this->createStandardProperties());
    }

    /**
     * @param array<mixed> $data
     */
    private function createPetitionWithTermEngineEventsWithoutWithdrawn(array $data): Petition
    {
        $events = collect([
            new PetitionEvent([
                'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
                'date' => CalendarDate::create('2021-01-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::FINAL_DECISION,
                'date' => CalendarDate::create('2022-01-01'),
            ]),
        ]);

        return $this->buildPetitionWithEvents($data, $events, $this->createStandardProperties());
    }

    /**
     * @param array<mixed> $data
     */
    private function createPetitionWithTermEngineEventsMinimal(array $data): Petition
    {
        return $this->buildPetitionWithEvents($data, collect([]), new CustomPetitionPropertyCollection([]));
    }

    private function createStandardProperties(): CustomPetitionPropertyCollection
    {
        return new CustomPetitionPropertyCollection([
            (new CustomPetitionProperty())->setAttribute('name', 'Binnen wettelijke termijn'),
            (new CustomPetitionProperty())->setAttribute('name', 'Binnen afgesproken termijn'),
            (new CustomPetitionProperty())->setAttribute('name', 'Gegrond'),
            (new CustomPetitionProperty())->setAttribute('name', 'Ongegrond'),
        ]);
    }

    /**
     * @param array<mixed> $data
     * @param Collection<int, PetitionEvent> $events
     */
    private function buildPetitionWithEvents(array $data, Collection $events, CustomPetitionPropertyCollection $properties): Petition
    {
        $petition = new Petition([
            'number' => $data['number'],
            'name' => $data['name'],
        ]);

        $petition->setRelation('petitionEvents', $events);
        $petition->setRelation('customPetitionProperties', $properties);
        $petition->setRelation('customDates', collect());

        return $petition;
    }
}
