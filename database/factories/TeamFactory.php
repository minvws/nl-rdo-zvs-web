<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\Team;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /** @var class-string<Team> $model */
    protected $model = Team::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'name' => 'Team ' . $this->faker->unique()->word(),
            'active' => true,
        ];
    }
}
