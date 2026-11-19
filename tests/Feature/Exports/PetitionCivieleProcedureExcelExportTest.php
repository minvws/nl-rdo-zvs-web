<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Collections\CustomPetitionPropertyCollection;
use App\Collections\PetitionEventCollection;
use App\Enums\ExportType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Exports\ExcelDate;
use App\Exports\ExportCriteria;
use App\Exports\PetitionCivieleProcedureExcelExport;
use App\Models\CustomPetitionProperty;
use App\Models\Petition;
use App\Models\PetitionEvent;
use App\Models\PetitionType;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Tests\Feature\FeatureTestCase;

use function __;
use function collect;

class PetitionCivieleProcedureExcelExportTest extends FeatureTestCase
{
    public function testMapReadsTheDatesFromTheFinalResultEvent(): void
    {
        $petition = new Petition([
            'number' => 'number',
            'name' => 'name',
            'date_of_entry' => CalendarDate::create('2021-01-01'),
        ]);
        $petition->setRelation('petitionEvents', new PetitionEventCollection([
            new PetitionEvent([
                'type' => PetitionEventType::COURT_SESSION,
                'date' => CalendarDate::create('2021-02-01'),
            ]),
            new PetitionEvent([
                'type' => PetitionEventType::FINAL_RESULT,
                'result_type' => ResultType::FINAL_DECISION,
                'date' => CalendarDate::create('2022-01-01'),
            ]),
        ]));
        $petition->setRelation('customPetitionProperties', new CustomPetitionPropertyCollection([
            (new CustomPetitionProperty())->setAttribute('name', 'Gegrond'),
        ]));
        $petition->setRelation('customDates', collect());

        $expectedResult = [
            'number',
            'name',
            ExcelDate::fromCalendarDate(CalendarDate::create('2021-01-01')),
            null,
            ExcelDate::fromCalendarDate(CalendarDate::create('2022-01-01')),
            'Deels gegrond',
        ];

        $this->assertEquals($expectedResult, $this->makePetitionCivieleProcedureExcelExport()->map($petition));
    }

    public function testHeadings(): void
    {
        $this->assertEquals([
            __('exports.reference'),
            __('exports.subject'),
            __('exports.date_of_entry'),
            __('exports.date_withdrawn'),
            __('exports.date_decision'),
            __('exports.decision'),
        ], $this->makePetitionCivieleProcedureExcelExport()->headings());
    }

    public function testColumnFormats(): void
    {
        $this->assertSame([
            'C' => ExcelDate::DATE_FORMAT,
            'D' => ExcelDate::DATE_FORMAT,
            'E' => ExcelDate::DATE_FORMAT,
        ], $this->makePetitionCivieleProcedureExcelExport()->columnFormats());
    }

    private function makePetitionCivieleProcedureExcelExport(): PetitionCivieleProcedureExcelExport
    {
        $startDate = $this->faker()->calendarDate();

        return new PetitionCivieleProcedureExcelExport(
            'worksheet',
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $startDate->addDays(30)),
            ),
        );
    }
}
