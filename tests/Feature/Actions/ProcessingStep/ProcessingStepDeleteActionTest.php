<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepDeleteAction;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\ProcessingStepAssignment;
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
        ProcessingStepAssignment::factory()->recycle($processingStep);

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

        // assert the relation is gone as well
        $this->assertDatabaseMissing(ProcessingStepAssignment::class, [
            'processing_step_id' => $processingStep->id,
        ]);
    }
}
