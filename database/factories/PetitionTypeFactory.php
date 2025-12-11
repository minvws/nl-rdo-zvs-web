<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PetitionTypeType;
use App\Models\Department;
use App\Models\PetitionType;

/**
 * @extends Factory<PetitionType>
 */
class PetitionTypeFactory extends Factory
{
    /** @var class-string<PetitionType> $model */
    protected $model = PetitionType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'department_id' => Department::factory(),
            'name' => $this->faker->sentence(3),
            'type' => $this->faker->randomElement(PetitionTypeType::cases()),
            'particularity_label' => $this->faker->optional(0.1)->word(),
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }
}
