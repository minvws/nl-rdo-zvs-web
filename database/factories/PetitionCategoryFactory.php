<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\PetitionCategory;

/**
 * @extends Factory<PetitionCategory>
 */
class PetitionCategoryFactory extends Factory
{
    /** @var class-string<PetitionCategory> $model */
    protected $model = PetitionCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'department_id' => Department::factory(),
            'name' => $this->faker->sentence(3),
            'active' => true,
        ];
    }
}
