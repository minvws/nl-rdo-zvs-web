<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PetitionEventType;
use App\Models\Petition;
use App\Models\PetitionEvent;

/**
 * @extends Factory<PetitionEvent>
 */
class PetitionEventFactory extends Factory
{
    /** @var class-string<PetitionEvent> $model */
    protected $model = PetitionEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => PetitionEventType::PRIMARY_DECISION->value,
            'date' => $this->faker->calendarDate(),
            'duration' => $this->faker->numberBetween(1, 100),
            'petition_id' => Petition::factory(),
        ];
    }

    public function withPenalties(): static
    {
        return $this->state(function () {
            return [
                'penalties' => [
                    [
                        'duration' => $this->faker->numberBetween(1, 50),
                        'amount' => $this->faker->numberBetween(100, 10_000),
                    ],
                ],
            ];
        });
    }

    public function withType(PetitionEventType $type): static
    {
        return $this->state(function () use ($type) {
            return [
                'type' => $type->value,
            ];
        });
    }
}
