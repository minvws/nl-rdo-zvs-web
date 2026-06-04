<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DecisionType;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Team;

/**
 * @extends Factory<Decision>
 */
class DecisionFactory extends Factory
{
    /** @var class-string<Decision> $model */
    protected $model = Decision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'department_id' => Department::factory(),
            'team_id' => Team::factory(),
            'name' => $this->faker->sentence(),
            'reference' => $this->faker->word(),
            'reviewbatch' => $this->faker->optional()->word(),
            'date' => $this->faker->calendarDate(),
            'archived_at' => null,
            'type' => $this->faker->randomElement(DecisionType::cases()),
        ];
    }
}
