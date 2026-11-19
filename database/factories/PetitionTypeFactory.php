<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PetitionVariant;
use App\Models\Department;
use App\Models\PetitionType;

use function array_filter;
use function in_array;

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
            // Verzet and civiele procedure always use term engine V2, so exclude them for the tests using this factory that expect V1 support
            'type' => $this->faker->randomElement(array_filter(
                PetitionVariant::cases(),
                static fn (PetitionVariant $variant): bool => !in_array(
                    $variant,
                    [PetitionVariant::VERZET, PetitionVariant::CIVIELE_PROCEDURE],
                    true,
                ),
            )),
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
