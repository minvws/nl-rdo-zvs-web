<?php

declare(strict_types=1);

namespace Tests\Feature\Exports;

use App\Enums\CustomDateLabel;
use App\Enums\ExportType;
use App\Exports\ExportCriteria;
use App\Exports\PetitionInternalExcelExportPetitionSheet;
use App\Models\Contact;
use App\Models\CustomPetitionProperty;
use App\Models\Petition;
use App\Models\PetitionCustomDate as PetitionCustomDateModel;
use App\Models\PetitionStatus;
use App\Models\PetitionTerm;
use App\Models\PetitionType;
use App\Models\PolicyDepartment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\DateRange;
use Illuminate\Database\Eloquent\Factories\Sequence;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Feature\FeatureTestCase;
use Tests\Helpers\ConfigHelper;

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
                'assigned_to' => $assignedUser->id,
                'deadline_at' => $this->faker->calendarDate(),
                'petition_status_id' => $petitionStatus->id,
                'date_appealed_decision' => $this->faker->calendarDate(),
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

        $petitionBeroepExcelExport = $this->makePetitionBeroepExcelExport();

        $expectedResult = [
            $petition->number,
            $petition->name,
            $petition->applicant->first()->display_name,
            $assignedUser->name,
            $petition->policyDepartments->toString(),
            $petition->petitionType->name,
            $petition->petitionCategory->name,
            $petition->date_of_entry->format('d-m-Y'),
            $petition->date_appealed_decision->format('d-m-Y'),
            $petition->deadline_at->format('d-m-Y'),
            $petition->created_at->timezone(ConfigHelper::get('app.display_timezone'))->format('d-m-Y H:i'),
            $petition->petitionTerms->penaltyToDate(CalendarDate::today()),
            $petition->petitionTerms->totalPenalty(),
            $petition->relatedPetitions->toString(),
            $petitionCustomDateWithDrawn->format('Y-m-d'),
            $petitionCustomDateDecisionAppeal->format('Y-m-d'),
            $petition->daysPending,
            __(sprintf('petition_status.%s', $petitionStatus->status_group->value)),
            '',
            '',
            '',
            '',
            '',
            null,
            null,
        ];

        $this->assertEquals($expectedResult, $petitionBeroepExcelExport->map($petition));
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

        $petitionBeroepExcelExport = $this->makePetitionBeroepExcelExport();
        $map = $petitionBeroepExcelExport->map($petition);

        $this->assertEquals($expectedResult, $map[$key]);
    }

    public static function customPetitionPropertyOptionsDataProvider(): array
    {
        return [
            [[], 18, ''],
            [[], 19, ''],
            [[], 20, ''],
            [[], 21, ''],
            [[], 22, ''],
            [['Binnen wettelijke termijn'], 18, 'Binnen wettelijke termijn'],
            [['Binnen wettelijke termijn'], 19, ''],
            [['Binnen wettelijke termijn', 'Binnen afgesproken termijn'], 18, 'Binnen wettelijke termijn, Binnen afgesproken termijn'],
            [['Binnen wettelijke termijn', 'Onbekende optie'], 18, 'Binnen wettelijke termijn'],
            [['Binnen wettelijke termijn', 'Onbekende optie'], 19, ''],
            [['Doorzending'], 18, ''],
            [['Doorzending'], 19, 'Doorzending'],
            [['Herziening – herstel bezwaar'], 20, 'Herziening - herstel bezwaar'], // watch it: dashes are different
            [['Herziening – herstel bezwaar', 'Informeel'], 20, 'Herziening - herstel bezwaar, Informeel'], // watch it: dashes are different
            [['A', 'D', 'E'], 21, 'A, D, E'],
            [['A', 'E', 'B'], 21, 'A, E, B'],
            [['A', 'D', 'E', 'Z'], 21, 'A, D, E'],
            [['A'], 22, ''],
            [['Gegrond', 'Intrekking', 'Kennelijk gegrond'], 22, 'Gegrond, Intrekking, Kennelijk gegrond'],
        ];
    }

    public function makePetitionBeroepExcelExport(): PetitionInternalExcelExportPetitionSheet
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
}
