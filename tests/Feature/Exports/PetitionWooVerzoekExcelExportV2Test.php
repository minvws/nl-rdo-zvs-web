<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionCustomDateCollection;
use App\Collections\PetitionEventCollection;
use App\Enums\AdjournmentEndReason;
use App\Enums\CustomDateLabel;
use App\Enums\ExportType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Enums\SuspensionType;
use App\Exports\ExcelDate;
use App\Exports\ExportCriteria;
use App\Exports\PetitionWooVerzoekExcelExport;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCustomDate;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Carbon\CarbonImmutable;
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
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')),
            __('exports.woo_verzoek.withdrawn'),
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-06-01')),
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
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')),
            null,
            null,
            ExcelDate::fromCalendarDate(CalendarDate::create('2022-01-01')),
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
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')),
            null,
            null,
            null,
            0,
            __('exports.true'),
            ExcelDate::fromCalendarDate(CalendarDate::create('2023-01-02')),
            __('exports.false'),
        ];

        $this->assertEquals($expectedResult, $petitionWooVerzoekExcelExport->map($row));
    }

    public function testMapFallsBackToTheSettlementDateFromBeforeTheConversion(): void
    {
        $row = $this->buildPetitionWithTermEngineEvents(['number' => 'number', 'name' => 'name'], collect([]));
        $row->setRelation('customDates', new PetitionCustomDateCollection([
            PetitionCustomDate::factory()->make([
                'date_label' => CustomDateLabel::DATE_SETTLEMENT_WITHOUT_DECISION,
                'date' => CalendarDate::create('2023-03-04'),
            ]),
        ]));

        $map = $this->makePetitionWooVerzoekExcelExport()->map($row);

        $this->assertEquals(ExcelDate::fromCalendarDate(CalendarDate::create('2023-03-04')), $map[4]);
    }

    public function testMapCountsAnOpenUnspecifiedAdjournmentUpToToday(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-10'),
                    'created_at' => CalendarDate::create('2025-01-10'),
                ]),
            ]),
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-02-01')),
        );

        // 2025-01-10 through 2025-01-31, the day the adjournment started counts, today does not yet.
        $this->assertSame(22, $export->map($row)[6]);
    }

    public function testMapDoesNotCountAdjournedDaysForADepartmentWhoseTermsKeepRunning(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-10'),
                    'created_at' => CalendarDate::create('2025-01-10'),
                ]),
            ]),
            'team-c',
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-02-01')),
        );

        $this->assertSame(0, $export->map($row)[6]);
    }

    public function testMapStopsCountingAnOpenUnspecifiedAdjournmentAtTheEndOfTheExportPeriod(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-10'),
                    'created_at' => CalendarDate::create('2025-01-10'),
                ]),
            ]),
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-01-20')),
        );

        // 2025-01-10 through 2025-01-20, the rest of the adjournment falls outside the period.
        $this->assertSame(11, $export->map($row)[6]);
    }

    public function testMapCountsAnEndedUnspecifiedAdjournmentUpToItsEndDate(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-10'),
                    'created_at' => CalendarDate::create('2025-01-10'),
                ]),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                    'date' => CalendarDate::create('2025-01-21'),
                    'created_at' => CalendarDate::create('2025-01-21'),
                    'reasoning' => AdjournmentEndReason::Event->value,
                ]),
            ]),
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-02-01')),
        );

        // 2025-01-10 through 2025-01-20, the day the adjournment ends counts towards the term again.
        $this->assertSame(11, $export->map($row)[6]);
    }

    public function testMapAddsSpecificationConsultationAndAdjournmentDaysTogether(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                $this->letterOfSuspension('2025-01-05', SuspensionType::SPECIFICATION, 5),
                $this->letterOfSuspension('2025-01-12', SuspensionType::CONSULTATION, 4),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-20'),
                    'created_at' => CalendarDate::create('2025-01-20'),
                ]),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                    'date' => CalendarDate::create('2025-01-25'),
                    'created_at' => CalendarDate::create('2025-01-25'),
                    'reasoning' => AdjournmentEndReason::Event->value,
                ]),
            ]),
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-02-01')),
        );

        // 5 days of "preciseren" plus 4 days of "zienswijze" plus 5 days of adjournment.
        $this->assertSame(14, $export->map($row)[6]);
    }

    public function testMapDoesNotCountAdjournedDaysThatAreAlreadySuspended(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::create(2025, 2, 1));

        $row = $this->buildPetitionWithTermEngineEvents(
            ['number' => 'number', 'name' => 'name'],
            collect([
                $this->petitionReceived(),
                $this->letterOfSuspension('2025-01-05', SuspensionType::SPECIFICATION, 10),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT,
                    'date' => CalendarDate::create('2025-01-10'),
                    'created_at' => CalendarDate::create('2025-01-10'),
                ]),
                new PetitionEvent([
                    'type' => PetitionEventType::UNSPECIFIED_ADJOURNMENT_END,
                    'date' => CalendarDate::create('2025-01-13'),
                    'created_at' => CalendarDate::create('2025-01-13'),
                    'reasoning' => AdjournmentEndReason::Event->value,
                ]),
            ]),
        );

        $export = $this->makePetitionWooVerzoekExcelExport(
            new DateRange(CalendarDate::create('2025-01-01'), CalendarDate::create('2025-02-01')),
        );

        // The adjournment runs inside the suspension, so those days are only counted once.
        $this->assertSame(10, $export->map($row)[6]);
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

    public function makePetitionWooVerzoekExcelExport(?DateRange $dateRange = null): PetitionWooVerzoekExcelExport
    {
        if (!$dateRange instanceof DateRange) {
            $startDate = $this->faker()->calendarDate();
            $dateRange = new DateRange($startDate, $startDate->addDays(30));
        }

        return new PetitionWooVerzoekExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                $dateRange,
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

    private function petitionReceived(): PetitionEvent
    {
        return new PetitionEvent([
            'type' => PetitionEventType::PETITION_RECEIVED,
            'date' => CalendarDate::create('2025-01-01'),
            'created_at' => CalendarDate::create('2025-01-01'),
            'duration' => 28,
        ]);
    }

    private function letterOfSuspension(string $date, SuspensionType $suspensionType, int $duration): PetitionEvent
    {
        return new PetitionEvent([
            'type' => PetitionEventType::LETTER_OF_SUSPENSION_SENT,
            'date' => CalendarDate::create($date),
            'created_at' => CalendarDate::create($date),
            'duration' => $duration,
            'suspension_type' => $suspensionType,
        ]);
    }

    /**
     * @param array<mixed> $data
     * @param Collection<int, PetitionEvent> $events
     */
    private function buildPetitionWithTermEngineEvents(
        array $data,
        Collection $events,
        string $departmentSlug = 'team-a',
    ): Petition {
        $petition = new Petition([
            'number' => $data['number'],
            'name' => $data['name'],
        ]);

        $petition->setRelation('department', new Department(['slug' => $departmentSlug]));

        $properties = new CustomPetitionPropertyCollection([]);
        $customDates = new PetitionCustomDateCollection([]);

        $petition->setRelation('petitionEvents', new PetitionEventCollection($events->all()));
        $petition->setRelation('customPetitionProperties', $properties);
        $petition->setRelation('customDates', $customDates);

        return Mockery::mock($petition)
            ->makePartial()
            ->shouldReceive('isTermEngineConverted')
            ->andReturn(true)
            ->getMock();
    }
}
