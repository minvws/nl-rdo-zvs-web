<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentRole;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionAssignment;
use App\Models\PetitionCategory;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\Team;
use Carbon\CarbonImmutable;

use function sprintf;

/**
 * @extends Factory<Petition>
 */
class PetitionFactory extends Factory
{
    /** @var class-string<Petition> $model */
    protected $model = Petition::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'petition_type_id' => PetitionType::factory(),
            'petition_status_id' => PetitionStatus::factory(),
            'petition_category_id' => PetitionCategory::factory(),
            'team_id' => Team::factory(),
            'name' => $this->faker->sentence(3),
            'message' => $this->faker->optional()->regexify('ZK-AFD-[A-Z]-[0-9]{4}'),
            'description' => $this->faker->optional()->text(),
            'date_of_entry' => $this->faker->calendarDate(),
            'date_of_message' => $this->faker->optional()->calendarDate(),
            'deadline_at' => $this->faker->optional()->calendarDate(),
            'decision_reference' => $this->faker->optional()->regexify('BEZ-[0-9]{4}-[A-Z]{2}'),
            'decision_date' => $this->faker->optional()->calendarDate(),
            'archived_at' => null,
        ];
    }

    public function configure(): static
    {
        $this->afterMaking->push(function (Petition $petition): void {
            if ($petition->number !== null) {
                return;
            }

            $department = $petition->department;
            $year = CarbonImmutable::now()->format('Y');
            $abbreviation = $department->abbreviation;
            $uniqueNumber = $this->faker->unique()->regexify('[0-9]{6}');
            $petition->number = sprintf('%s%s%s', $year, $abbreviation, $uniqueNumber);
        });

        $this->afterCreating->push(function (Petition $petition): void {
            $this->createCustomDates($petition);
        });

        return $this;
    }

    public function withFirstAssignee(): static
    {
        return $this->afterCreating(function (Petition $petition): void {
            PetitionAssignment::factory()->for($petition)->create([
                'assignment_role' => AssignmentRole::PRIMARY,
            ]);
        });
    }

    public function withSecondAssignee(): static
    {
        return $this->afterCreating(function (Petition $petition): void {
            PetitionAssignment::factory()->for($petition)->create([
                'assignment_role' => AssignmentRole::SECONDARY,
            ]);
        });
    }

    private function createCustomDates(Petition $petition): void
    {
        $petitionType = PetitionType::find($petition->petition_type_id);

        if (!$petitionType instanceof PetitionType) {
            return;
        }

        $customDateLabels = $petitionType->customDateLabels;

        foreach ($customDateLabels as $customDateLabel) {
            if ($this->faker->boolean(30)) {
                $petition
                    ->customDates()
                    ->create([
                        'date_label' => $customDateLabel->date_label,
                        'date' => $this->faker->calendarDate(),
                    ]);
            }
        }
    }
}
