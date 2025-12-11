<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\PetitionNumber;

/**
 * @extends Factory<PetitionNumber>
 */
class PetitionNumberFactory extends Factory
{
    /** @var class-string<PetitionNumber> $model */
    protected $model = PetitionNumber::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'year' => (int) $this->faker->calendarDate()->format('Y'),
            'number' => $this->faker->randomNumber(),
        ];
    }
}
