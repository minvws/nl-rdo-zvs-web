<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\AssignmentRole;
use App\Enums\CustomDateLabel;
use App\Enums\ExportType;
use App\Enums\PetitionEventType;
use App\Enums\ResultType;
use App\Exports\ExcelDate;
use App\Exports\ExportCriteria;
use App\Exports\PetitionInternalExcelExportPetitionSheet;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Decision;
use App\Models\Petition;
use App\Models\PetitionCustomDate as PetitionCustomDateModel;
use App\Models\PetitionEvent;
use App\Models\PetitionStatus;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;

use function __;
use function count;
use function sprintf;

class PetitionInternalExcelExportPetitionSheetTest extends FeatureTestCase
{
    public function testMap(): void
    {
        $petitionCustomDateWithDrawn = $this->faker->calendarDate();
        $petitionCustomDateDecisionAppeal = $this->faker->calendarDate();

        $applicant = Contact::factory()->create();
        $assignedUser = User::factory()->create();
        $petitionStatus = PetitionStatus::factory()->create();

        $petition = Petition::factory()
            ->hasAttached($applicant, ['role' => 'applicant'])
            ->has(PolicyDepartment::factory()->count(3))
            ->has(PetitionTerm::factory()->count(3), 'petitionTerms')
            ->has(Petition::factory(), 'relatedPetitions')
            ->create([
                'deadline_at' => $this->faker->calendarDate(),
                'petition_status_id' => $petitionStatus->id,
                'date_appealed_decision' => $this->faker->calendarDate(),
                'legacy_term_penalty_today' => 100,
                'igs_penalty_today' => 20,
                'bnt_penalty_today' => 3,
                'legacy_term_forfeited' => 4000,
                'igs_forfeited' => 500,
                'bnt_forfeited' => 60,
            ]);

        $petition->assignments()->create([
            'user_id' => $assignedUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        // Create the custom dates using the new relationship
        PetitionCustomDateModel::factory()->create([
            'petition_id' => $petition->id,
            'date' => $petitionCustomDateWithDrawn,
            'date_label' => CustomDateLabel::DATE_WITHDRAWN,
        ]);

        PetitionCustomDateModel::factory()->create([
            'petition_id' => $petition->id,
            'date' => $petitionCustomDateDecisionAppeal,
            'date_label' => CustomDateLabel::DATE_DECISION_ON_APPEAL,
        ]);

        $petitionSheet = $this->makePetitionSheet();

        $expectedResult = [
            $petition->number,
            $petition->name,
            $petition->applicant->first()->display_name,
            $assignedUser->name,
            $petition->policyDepartments->toString(),
            $petition->petitionType->name,
            $petition->petitionCategory->name,
            $petition->team?->name,
            ExcelDate::fromCalendarDate($petition->date_of_entry),
            ExcelDate::fromCalendarDate($petition->date_appealed_decision),
            ExcelDate::fromCalendarDate($petition->deadline_at),
            ExcelDate::fromDateTime($petition->created_at),
            123,
            4560,
            $petition->relatedPetitions->toString(),
            ExcelDate::fromCalendarDate($petitionCustomDateWithDrawn),
            ExcelDate::fromCalendarDate($petitionCustomDateDecisionAppeal),
            $petition->daysPending,
            __(sprintf('petition_status.%s', $petitionStatus->status_group->value)),
            $petitionStatus->status,
            '',
            '',
            '',
            '',
            '',
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionSheet->map($petition));
    }

    /**
     * @param array<array-key, mixed> $customPetitionProperties
     */
    #[DataProvider('customPetitionPropertyOptionsDataProvider')]
    public function testMapOfCustomOptions(array $customPetitionProperties, int $key, string $expectedResult): void
    {
        $customPetitionProperties = CustomPetitionProperty::factory()
            ->count(count($customPetitionProperties))
            ->sequence(function (Sequence $sequence) use ($customPetitionProperties) {
                return [
                    'name' => $customPetitionProperties[$sequence->index],
                ];
            });

        $petition = Petition::factory()
            ->has($customPetitionProperties)
            ->create();

        $mockedPetition = $this->mockPetitionAsLegacy($petition);

        $petitionSheet = $this->makePetitionSheet();
        $map = $petitionSheet->map($mockedPetition);

        $this->assertEquals($expectedResult, $map[$key]);
    }

    public static function customPetitionPropertyOptionsDataProvider(): array
    {
        return [
            [[], 20, ''],
            [[], 21, ''],
            [[], 22, ''],
            [[], 23, ''],
            [[], 24, ''],
            [['Binnen wettelijke termijn'], 20, 'Binnen wettelijke termijn'],
            [['Binnen wettelijke termijn'], 21, ''],
            [['Binnen wettelijke termijn', 'Binnen afgesproken termijn'], 20, 'Binnen wettelijke termijn, Binnen afgesproken termijn'],
            [['Binnen wettelijke termijn', 'Onbekende optie'], 20, 'Binnen wettelijke termijn'],
            [['Binnen wettelijke termijn', 'Onbekende optie'], 21, ''],
            [['Doorzending'], 20, ''],
            [['Doorzending'], 21, 'Doorzending'],
            [['Herziening – herstel bezwaar'], 22, 'Herziening - herstel bezwaar'], // watch it: dashes are different
            [['Herziening – herstel bezwaar', 'Informeel'], 22, 'Herziening - herstel bezwaar, Informeel'], // watch it: dashes are different
            [['A', 'D', 'E'], 23, 'A, D, E'],
            [['A', 'E', 'B'], 23, 'A, B, E'],
            [['E', 'D', 'A'], 23, 'A, D, E'],
            [['A', 'D', 'E', 'Z'], 23, 'A, D, E'],
            [['A'], 24, ''],
            [['Gegrond', 'Intrekking', 'Kennelijk gegrond'], 24, 'Gegrond, Intrekking, Kennelijk gegrond'],
        ];
    }

    public function testMapUsesTheDeadlineOverrideWhenItIsSet(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => CalendarDate::create('01-03-2026'),
            'deadline_override_at' => CalendarDate::create('30-06-2026'),
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $row = $this->makePetitionSheet()->map($petition);

        $this->assertEquals(ExcelDate::fromCalendarDate(CalendarDate::create('30-06-2026')), $row[10]);
    }

    public function testMapUsesTheCalculatedDeadlineWithoutAnOverride(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => CalendarDate::create('01-03-2026'),
            'deadline_override_at' => null,
            'deadline_override_reason' => null,
        ]);

        $row = $this->makePetitionSheet()->map($petition);

        $this->assertEquals(ExcelDate::fromCalendarDate(CalendarDate::create('01-03-2026')), $row[10]);
    }

    public function testColumnFormats(): void
    {
        $this->assertSame([
            'I' => ExcelDate::DATE_FORMAT,
            'J' => ExcelDate::DATE_FORMAT,
            'K' => ExcelDate::DATE_FORMAT,
            'L' => ExcelDate::DATETIME_FORMAT,
            'P' => ExcelDate::DATE_FORMAT,
            'Q' => ExcelDate::DATE_FORMAT,
            'Z' => ExcelDate::DATE_FORMAT,
            'AA' => ExcelDate::DATE_FORMAT,
        ], $this->makePetitionSheet()->columnFormats());
    }

    public function makePetitionSheet(): PetitionInternalExcelExportPetitionSheet
    {
        $startDate = $this->faker()->calendarDate();
        $endDate = $startDate->addDays(30);

        return new PetitionInternalExcelExportPetitionSheet(
            new ExportCriteria(
                new PetitionType(),
                $this->faker()->randomElement(ExportType::cases()),
                new DateRange($startDate, $endDate),
            ),
        );
    }

    public function testForwardingWithTermEngineConvertedReturnsDoorzending(): void
    {
        $petition = Petition::factory()->create();

        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::FINAL_RESULT,
            'result_type' => ResultType::FORWARDED,
            'date' => CalendarDate::today(),
        ]);

        $export = $this->makePetitionSheet();
        $map = $export->map($petition);

        $this->assertEquals('Doorzending', $map[21]);
    }

    public function testForwardingWithTermEngineConvertedNoForwardEventReturnsEmpty(): void
    {
        $petition = Petition::factory()->create();

        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::FINAL_RESULT,
            'result_type' => ResultType::FINAL_DECISION,
            'date' => CalendarDate::today(),
        ]);

        $export = $this->makePetitionSheet();
        $map = $export->map($petition);

        $this->assertEquals('', $map[20]);
    }

    public function testForwardingWithLegacyPetitionReturnsCustomPropertyValue(): void
    {
        $customProperty = CustomPetitionProperty::factory()->create([
            'name' => 'Doorzending',
        ]);

        $petition = Petition::factory()
            ->hasAttached($customProperty)
            ->create();

        $mockedPetition = $this->mockPetitionAsLegacy($petition);

        $export = $this->makePetitionSheet();
        $map = $export->map($mockedPetition);

        $this->assertEquals('Doorzending', $map[21]);
    }

    /**
     * @param array<int, bool> $expectedFilledColumns
     */
    #[DataProvider('finalResultDatesDataProvider')]
    public function testFinalResultDatesWithTermEngineConverted(
        ResultType $resultType,
        array $expectedFilledColumns,
    ): void
    {
        $date = CalendarDate::create('15-04-2026');
        $petition = Petition::factory()->create();

        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::FINAL_RESULT,
            'result_type' => $resultType,
            'date' => $date,
        ]);

        $map = $this->makePetitionSheet()->map($petition);

        foreach ($expectedFilledColumns as $column => $filled) {
            $expected = $filled ? ExcelDate::fromCalendarDate($date) : null;
            $this->assertEquals($expected, $map[$column], sprintf('column %d', $column));
        }
    }

    public static function finalResultDatesDataProvider(): array
    {
        // 15: date withdrawn, 16: date decision on appeal, 25: date settlement without decision
        // 26 (date decision) comes from the linked decisions, not the final result
        return [
            'final decision' => [ResultType::FINAL_DECISION, [15 => false, 16 => true, 25 => false, 26 => false]],
            'withdrawn' => [ResultType::WITHDRAWN, [15 => true, 16 => false, 25 => true, 26 => false]],
            'forwarded' => [ResultType::FORWARDED, [15 => false, 16 => false, 25 => true, 26 => false]],
        ];
    }

    public function testDateOfDecisionWithTermEngineConvertedUsesLatestLinkedDecision(): void
    {
        $petition = Petition::factory()
            ->hasAttached(Decision::factory()->state(['date' => CalendarDate::create('01-12-2026')]))
            ->hasAttached(Decision::factory()->state(['date' => CalendarDate::create('22-12-2026')]))
            ->create();

        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::FINAL_RESULT,
            'result_type' => ResultType::WITHDRAWN,
            'date' => CalendarDate::create('15-04-2026'),
        ]);

        $map = $this->makePetitionSheet()->map($petition);

        $this->assertEquals(ExcelDate::fromCalendarDate(CalendarDate::create('22-12-2026')), $map[26]);
    }

    public function testFinalResultDatesWithTermEngineConvertedWithoutFinalResult(): void
    {
        $petition = Petition::factory()->create();

        PetitionEvent::factory()->create([
            'petition_id' => $petition->id,
            'type' => PetitionEventType::RECEIPT_OF_OBJECTION,
        ]);

        $map = $this->makePetitionSheet()->map($petition);

        $this->assertNull($map[15]);
        $this->assertNull($map[16]);
        $this->assertNull($map[25]);
        $this->assertNull($map[26]);
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
