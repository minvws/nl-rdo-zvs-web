<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Enums\ExportType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionWooVerzoekExcelExport;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function collect;

class PetitionWooVerzoekExcelExportV2Test extends FeatureTestCase
{
    /**
     * @param array<mixed> $data
     */
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedWithdrawn(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEventsWithdrawn($data);
        $petitionWooVerzoekExcelExport = $this->makePetitionWooVerzoekExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            __('exports.woo_verzoek.withdrawn'),
            '2021-06-01',
            null,
            0,
            __('exports.false'),
            null,
            __('exports.false'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedFinalDecision(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEventsFinalDecision($data);
        $petitionWooVerzoekExcelExport = $this->makePetitionWooVerzoekExcelExport();

        $expectedResult = [
            'number',
            'name',
            '2021-01-01',
            null,
            null,
            '2022-01-01',
            0,
            __('exports.false'),
            null,
            __('exports.false'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->map($row));
    }

    /**
     * @param array<mixed> $data
     */
    #[DataProvider('excelExportDataProviderV2')]
    public function testMapWithTermEngineConvertedMeetingScheduled(array $data): void
    {
        $row = $this->createPetitionWithTermEngineEventsMeetingScheduled($data);
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
    private function createPetitionWithTermEngineEventsWithdrawn(array $data): Petition
    {
        $events = collect([
            new PetitionEvent([
                'type' => PetitionEventType::PETITION_RECEIVED,
                'date' => CalendarDate::create('2021-01-01'),
                'created_at' => CalendarDate::create('2021-01-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::WITHDRAWN,
                'date' => CalendarDate::create('2021-06-01'),
                'created_at' => CalendarDate::create('2021-06-01'),
            ]),
        ]);

        return $this->buildPetitionWithTermEngineEvents($data, $events);
    }

    /**
     * @param array<mixed> $data
     */
    private function createPetitionWithTermEngineEventsFinalDecision(array $data): Petition
    {
        $events = collect([
            new PetitionEvent([
                'type' => PetitionEventType::PETITION_RECEIVED,
                'date' => CalendarDate::create('2021-01-01'),
                'created_at' => CalendarDate::create('2021-01-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::FINAL_DECISION,
                'date' => CalendarDate::create('2022-01-01'),
                'created_at' => CalendarDate::create('2022-01-01'),
            ]),
        ]);

        return $this->buildPetitionWithTermEngineEvents($data, $events);
    }

    /**
     * @param array<mixed> $data
     */
    private function createPetitionWithTermEngineEventsMeetingScheduled(array $data): Petition
    {
        $events = collect([
            new PetitionEvent([
                'type' => PetitionEventType::PETITION_RECEIVED,
                'date' => CalendarDate::create('2021-01-01'),
                'created_at' => CalendarDate::create('2021-01-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::MEETING_SCHEDULED,
                'date' => CalendarDate::create('2023-01-02'),
                'created_at' => CalendarDate::create('2023-01-02'),
            ]),
        ]);

        return $this->buildPetitionWithTermEngineEvents($data, $events);
    }

    /**
     * @param array<mixed> $data
     * @param Collection<int, PetitionEvent> $events
     */
    private function buildPetitionWithTermEngineEvents(array $data, Collection $events): Petition
    {
        $petition = new Petition([
            'number' => $data['number'],
            'name' => $data['name'],
        ]);

        $properties = new CustomPetitionPropertyCollection([]);
        $customDates = new PetitionCustomDateCollection([]);

        $petition->setRelation('petitionEvents', $events);
        $petition->setRelation('customPetitionProperties', $properties);
        $petition->setRelation('customDates', $customDates);

        return Mockery::mock($petition)
            ->makePartial()
            ->shouldReceive('isTermEngineConverted')
            ->andReturn(true)
            ->getMock();
    }
}
