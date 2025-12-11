<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Petition;
use App\Models\PetitionDraftTerm;

/**
 * @extends Factory<PetitionDraftTerm>
 */
class PetitionDraftTermFactory extends Factory
{
    /** @var class-string<PetitionDraftTerm> $model */
    protected $model = PetitionDraftTerm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'petition_id' => Petition::factory(),
            'description' => $this->faker->optional()->sentence(),
            'start_date' => $this->faker->calendarDate(),
            'event_date' => $this->faker->optional()->calendarDate(),
            'days_after_event' => $this->faker->numberBetween(0, 365),
            'date_withdrawal' => $this->faker->optional()->calendarDate(),
            'days_after_date_withdrawal' => $this->faker->optional()->numberBetween(0, 365),
        ];
    }
}
