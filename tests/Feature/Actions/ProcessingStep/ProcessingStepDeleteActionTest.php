<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepDeleteAction;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepDeleteActionTest extends FeatureTestCase
{
    #[Test]
    public function deleteRemovesProcessingStepFromDatabase(): void
    {
        // Create a decision processing step
        $decision = Decision::factory()->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create();

        $user = User::factory()->create();

        // Verify it exists first
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $processingStep->id,
        ]);

        // Execute the action
        $action = new ProcessingStepDeleteAction($this->app->make(DatabaseManager::class));
        $action->execute($processingStep, $user);

        // Assert results
        $this->assertDatabaseMissing(ProcessingStep::class, [
            'id' => $processingStep->id,
        ]);
    }

    #[Test]
    public function deleteWorksWhenAssignedToUserExists(): void
    {
        // Create assigned user
        $assignedUser = User::factory()->create();

        // Create a decision processing step with assigned user
        $decision = Decision::factory()->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create([
                'assigned_to' => $assignedUser->id,
                'status' => ProcessingStepStatus::PENDING,
            ]);

        $user = User::factory()->create();

        // Verify it exists first
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $processingStep->id,
            'assigned_to' => $assignedUser->id,
        ]);

        // Execute the action
        $action = new ProcessingStepDeleteAction($this->app->make(DatabaseManager::class));
        $action->execute($processingStep, $user);

        // Assert results
        $this->assertDatabaseMissing(ProcessingStep::class, [
            'id' => $processingStep->id,
        ]);

        // Verify the user still exists (not deleted)
        $this->assertDatabaseHas(User::class, [
            'id' => $assignedUser->id,
        ]);
    }
}
