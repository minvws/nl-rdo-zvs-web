<?php

declare(strict_types=1);

namespace App\Console\Commands\App;

use App\Enums\PetitionTypeType;
use App\Enums\StatusGroup;
use App\Enums\TermType;
use App\Models\CustomPetitionProperty;
use App\Models\Department;
use App\Models\DepartmentTermTypeSetting;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\UuidInterface;

/**
 * @codeCoverageIgnore
 */
#[Signature('app:create-team-wjz-klachten')]
#[Description('Command description')]
class CreateTeamWjzKlachten extends Command
{
    public function __construct(private readonly DatabaseManager $databaseManager)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->databaseManager->transaction(function (): void {
            $department = $this->createDepartment();
            $this->createPetitionTypes($department);
            $this->createPetitionCategories($department);
//            $this->createDepartmentTermTypeSettings($department);
        });
    }

    private function createDepartment(): Department
    {
        return Department::query()->create([
            'abbreviation' => 'K',
            'slug' => 'team-wjz-klachten',
            'name' => 'Team WJZ Klachten',
        ]);
    }

    private function createPetitionTypes(Department $department): void
    {
        $typeNames = $this->getPetitionTypeNames();

        foreach ($typeNames as $typeName) {
            $petitionType = $this->createPetitionType($typeName, $department->id);
            $this->createPetitionStatuses($petitionType->id);
            $this->createCustomPetitionProperties($petitionType->id);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getPetitionTypeNames(): array
    {
        return [
            'WJZ-klacht',
            'Overige VWS-klacht',
            'Niet VWS-klacht',
            'NO-klacht VWS',
            'NO-doorzending',
            'NO-interventie',
            'Geen klacht',
        ];
    }

    private function createPetitionType(string $name, UuidInterface $departmentId): PetitionType
    {
        return PetitionType::query()->create([
            'name' => $name,
            'department_id' => $departmentId,
            'type' => PetitionTypeType::BEROEP,
            'active' => true,
        ]);
    }

    private function createPetitionStatuses(UuidInterface $petitionTypeId): void
    {
        $statuses = [
            ['status_group' => StatusGroup::PENDING, 'status' => 'In behandeling', 'order' => 10, 'bg_color' => '#D0EBFF'],
            ['status_group' => StatusGroup::CLOSED, 'status' => 'Afgehandeld', 'order' => 20, 'bg_color' => '#B2F2BB'],
        ];

        foreach ($statuses as $statusData) {
            PetitionStatus::query()->create([
                'petition_type_id' => $petitionTypeId,
                ...$statusData,
            ]);
        }
    }

    private function createCustomPetitionProperties(UuidInterface $petitionTypeId): void
    {
        $properties = $this->getCustomPetitionPropertiesData();

        foreach ($properties as $propertyData) {
            CustomPetitionProperty::query()->create([
                'petition_type_id' => $petitionTypeId,
                ...$propertyData,
            ]);
        }
    }

    /**
     * @return array<int, array<string, int|string|null>|array<string, int|string>>
     */
    private function getCustomPetitionPropertiesData(): array
    {
        return [
            ['name' => 'Uitkomst', 'type' => 'name', 'ordering' => 1, 'grouping' => null],
            ['name' => 'Uitkomst doorgeven', 'type' => 'title', 'ordering' => 2, 'grouping' => null],
            ['name' => 'Uitspraak', 'type' => 'subtitle', 'ordering' => 3, 'grouping' => null],
            ['name' => 'Gegrond', 'type' => 'option', 'ordering' => 4, 'grouping' => 1],
            ['name' => 'Deels gegrond', 'type' => 'option', 'ordering' => 5, 'grouping' => 1],
            ['name' => 'Ongegrond', 'type' => 'option', 'ordering' => 6, 'grouping' => 1],
            ['name' => 'Geen oordeel', 'type' => 'option', 'ordering' => 7, 'grouping' => 1],
            ['name' => 'Niet behandeld als klacht', 'type' => 'option', 'ordering' => 8, 'grouping' => 1],
            ['name' => 'Ingetrokken', 'type' => 'option', 'ordering' => 9, 'grouping' => 1],
            ['name' => 'Niet ontvankelijk 9:8 AWB', 'type' => 'option', 'ordering' => 10, 'grouping' => 1],
        ];
    }

    private function createPetitionCategories(Department $department): void
    {
        $categoryNames = $this->getPetitionCategoryNames();

        foreach ($categoryNames as $categoryName) {
            PetitionCategory::query()->create([
                'name' => $categoryName,
                'department_id' => $department->id,
                'active' => true,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function getPetitionCategoryNames(): array
    {
        return [
            'Bejegening',
            'Bereikbaarheid',
            'Digitale dienstverlening',
            'Informatieverstrekking',
            'Onzorgvuldigheid',
            'Tijdigheid',
            'Uitvoering wet- en regelgeving',
            'Overig',
        ];
    }

    // @phpstan-ignore-next-line
    private function createDepartmentTermTypeSettings(Department $department): void
    {
        $settings = $this->getDepartmentTermTypeSettingsData($department->id);

        foreach ($settings as $settingData) {
            // @phpstan-ignore-next-line
            DepartmentTermTypeSetting::query()->create($settingData);
        }
    }

    // @phpstan-ignore-next-line
    private function getDepartmentTermTypeSettingsData(UuidInterface $departmentId): array
    {
        return [
            ...$this->getTermTypeSettings($departmentId, TermType::FIRST, ['start_date' => [true, null], 'duration_in_days' => [true, 42]]),
            ...$this->getTermTypeSettings($departmentId, TermType::SECOND, ['duration_in_days' => [true, 28]]),
            ...$this->getTermTypeSettings($departmentId, TermType::THIRD, ['duration_in_days' => [true, 14]]),
        ];
    }

    /**
     * @param array<string, array<bool>>|array<string, array<int>>|array<string, array<null>> $activeSettings
     */
    // @phpstan-ignore-next-line
    private function getTermTypeSettings(UuidInterface $departmentId, TermType $termType, array $activeSettings = []): array
    {
        $fields = [
            'start_date',
            'duration_in_days',
            'penalty_amount_in_euros',
            'penalty_terms',
            'end_date',
            'date_appealed_decision',
        ];

        $settings = [];
        foreach ($fields as $field) {
            [$active, $defaultValue] = $activeSettings[$field] ?? [false, null];

            $settings[] = [
                'department_id' => $departmentId,
                'term_type' => $termType,
                'field' => $field,
                'active' => $active,
                'default_value' => $defaultValue,
            ];
        }

        return $settings;
    }
}
