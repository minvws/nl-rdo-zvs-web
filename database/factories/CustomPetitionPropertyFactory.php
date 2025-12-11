<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomPetitionPropertyType;
use App\Models\CustomPetitionProperty;
use App\Models\PetitionType;

/**
 * @extends Factory<CustomPetitionProperty>
 */
class CustomPetitionPropertyFactory extends Factory
{
    /** @var class-string<CustomPetitionProperty> $model */
    protected $model = CustomPetitionProperty::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_type_id' => PetitionType::factory(),
            'name' => $this->faker->word(),
            'type' => $this->faker->randomElement(CustomPetitionPropertyType::cases()),
            'ordering' => $this->faker->numberBetween(1, 100),
            'grouping' => null,
        ];
    }
}
