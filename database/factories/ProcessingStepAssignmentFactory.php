<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AssignmentRole;
use App\Models\ProcessingStep;
use App\Models\ProcessingStepAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingStepAssignment>
 */
class ProcessingStepAssignmentFactory extends Factory
{
    /** @var class-string<ProcessingStepAssignment> $model */
    protected $model = ProcessingStepAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'processing_step_id' => ProcessingStep::factory(),
            'user_id' => User::factory(),
            'assignment_role' => AssignmentRole::PRIMARY,
        ];
    }
}
