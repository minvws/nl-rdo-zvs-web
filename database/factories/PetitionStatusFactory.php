<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\StatusGroup;
use App\Models\PetitionStatus;
use App\Models\PetitionType;

/**
 * @extends Factory<PetitionStatus>
 */
class PetitionStatusFactory extends Factory
{
    /** @var class-string<PetitionStatus> $model */
    protected $model = PetitionStatus::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_type_id' => PetitionType::factory(),
            'status_group' => $this->faker->randomElement(StatusGroup::cases()),
            'status' => $this->faker->unique()->word(),
            'order' => $this->faker->unique()->numberBetween(1, 100),
            'bg_color' => $this->faker->randomElement(
                ['#FFDEEB', '#FCC2D7', '#FAA2C1', '#F8F0FC', '#F3D9FA', '#EEBEFA', '#FFE8CC', '#FFD8A8', '#D0EBFF', '#A5D8FF', '#FFF3BF', '#FFEC99', '#B2F2BB'],
            ),
        ];
    }
}
