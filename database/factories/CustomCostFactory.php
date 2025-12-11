<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomCostType;
use App\Models\CustomCost;
use App\Models\Petition;

/**
 * @extends Factory<CustomCost>
 */
class CustomCostFactory extends Factory
{
    /** @var class-string<CustomCost> $model */
    protected $model = CustomCost::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_id' => Petition::factory(),
            'custom_cost_type' => $this->faker->randomElement(CustomCostType::cases()),
            'custom_cost_amount_in_cents' => $this->faker->numberBetween(100, 999_999),
        ];
    }
}
