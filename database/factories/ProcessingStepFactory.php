<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;

/**
 * @extends Factory<ProcessingStep>
 */
class ProcessingStepFactory extends Factory
{
    /** @var class-string<ProcessingStep> $model */
    protected $model = ProcessingStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'status' => $this->faker->randomElement(ProcessingStepStatus::cases()),
            'decision_id' => Decision::factory(),
            'deadline_at' => $this->faker->optional()->calendarDate(),
            'ordering' => $this->faker->numberBetween(0, 100),
        ];
    }
}
