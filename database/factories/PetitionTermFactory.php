<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TermType;
use App\Models\Petition;
use App\Models\PetitionTerm;

/**
 * @extends Factory<PetitionTerm>
 */
class PetitionTermFactory extends Factory
{
    /** @var class-string<PetitionTerm> $model */
    protected $model = PetitionTerm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_id' => Petition::factory(),
            'type' => $this->faker->randomElement(TermType::cases()),
            'start_date' => $this->faker->calendarDate(),
            'duration_in_days' => $this->faker->numberBetween(0, 100),
            'penalty_amount_in_euros' => $this->faker->numberBetween(0, 100),
        ];
    }
}
