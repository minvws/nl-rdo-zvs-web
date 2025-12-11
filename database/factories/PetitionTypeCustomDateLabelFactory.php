<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CustomDateLabel;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;

/**
 * @extends Factory<PetitionTypeCustomDateLabel>
 */
class PetitionTypeCustomDateLabelFactory extends Factory
{
    /** @var class-string<PetitionTypeCustomDateLabel> $model */
    protected $model = PetitionTypeCustomDateLabel::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'petition_type_id' => PetitionType::factory(),
            'date_label' => $this->faker->randomElement(CustomDateLabel::cases()),
        ];
    }
}
