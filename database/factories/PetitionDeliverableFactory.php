<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PetitionDeliverableType;
use App\Models\Petition;
use App\Models\PetitionDeliverable;

/**
 * @extends Factory<PetitionDeliverable>
 */
class PetitionDeliverableFactory extends Factory
{
    /** @var class-string<PetitionDeliverable> $model */
    protected $model = PetitionDeliverable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_id' => Petition::factory(),
            'type' => $this->faker->randomElement(PetitionDeliverableType::cases()),
            'deadline_at' => $this->faker->calendarDate(),
            'description' => $this->faker->sentence(),
        ];
    }
}
