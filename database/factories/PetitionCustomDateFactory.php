<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomDateLabel;
use App\Models\Petition;
use App\Models\PetitionCustomDate;

/**
 * @extends Factory<PetitionCustomDate>
 */
class PetitionCustomDateFactory extends Factory
{
    /** @var class-string<PetitionCustomDate> $model */
    protected $model = PetitionCustomDate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_id' => Petition::factory(),
            'date_label' => $this->faker->randomElement(CustomDateLabel::cases()),
            'date' => $this->faker->optional(0.7)->calendarDate(),
        ];
    }
}
