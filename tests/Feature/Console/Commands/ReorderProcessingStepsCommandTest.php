<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Models\Decision;
use App\Models\ProcessingStep;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\Feature\FeatureTestCase;

final class ReorderProcessingStepsCommandTest extends FeatureTestCase
{
    #[Test]
    public function itHandlesNoDecisionsWithProcessingSteps(): void
    {
        Decision::factory()->create();

        $this->artisan('processing-steps:reorder')
            ->expectsOutputToContain('No decisions with processing steps found')
            ->assertSuccessful();
    }

    #[Test]
    public function itReordersStepsForSingleDecision(): void
    {
        $decision = Decision::factory()->create();

        $step1 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 3,
            'deadline_at' => '2025-01-01',
        ]);

        $step2 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-02',
        ]);

        $this->artisan('processing-steps:reorder')
            ->assertSuccessful();

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step1->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step2->id,
            'ordering' => 2,
        ]);
    }

    #[Test]
    public function itReordersStepsForMultipleDecisions(): void
    {
        $decision1 = Decision::factory()->create();
        $decision2 = Decision::factory()->create();

        $step1a = ProcessingStep::factory()->create([
            'decision_id' => $decision1->id,
            'ordering' => 2,
            'deadline_at' => '2025-01-01',
        ]);

        $step1b = ProcessingStep::factory()->create([
            'decision_id' => $decision1->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-02',
        ]);

        $step2a = ProcessingStep::factory()->create([
            'decision_id' => $decision2->id,
            'ordering' => 2,
            'deadline_at' => '2025-01-01',
        ]);

        $step2b = ProcessingStep::factory()->create([
            'decision_id' => $decision2->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-02',
        ]);

        $this->artisan('processing-steps:reorder')
            ->assertSuccessful();

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step1a->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step1b->id,
            'ordering' => 2,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step2a->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step2b->id,
            'ordering' => 2,
        ]);
    }

    #[Test]
    public function itSkipsReorderingWhenAlreadyInCorrectOrder(): void
    {
        $decision = Decision::factory()->create();

        $step1 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-01',
        ]);

        $step2 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 2,
            'deadline_at' => '2025-01-02',
        ]);

        $this->artisan('processing-steps:reorder')
            ->assertSuccessful();

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step1->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step2->id,
            'ordering' => 2,
        ]);
    }

    #[Test]
    public function itMaintainsSequentialOrdering(): void
    {
        $decision = Decision::factory()->create();

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 5,
            'deadline_at' => '2025-01-01',
        ]);

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 3,
            'deadline_at' => '2025-01-02',
        ]);

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-03',
        ]);

        $this->artisan('processing-steps:reorder')
            ->assertSuccessful();

        $steps = $decision->processingSteps()->reorder()->oldest('deadline_at')->get();

        $this->assertEquals(1, $steps[0]->ordering);
        $this->assertEquals(2, $steps[1]->ordering);
        $this->assertEquals(3, $steps[2]->ordering);
    }

    #[Test]
    public function itSortsByDeadlineThenCreatedAt(): void
    {
        $decision = Decision::factory()->create();

        // Same deadline_at — created later, should be ordering 2
        $step1 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-01',
            'created_at' => '2025-01-01 10:00:00',
        ]);

        // Same deadline_at — created earlier, should be ordering 1
        $step2 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 2,
            'deadline_at' => '2025-01-01',
            'created_at' => '2025-01-01 09:00:00',
        ]);

        // Later deadline — should be ordering 3
        $step3 = ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 3,
            'deadline_at' => '2025-01-02',
            'created_at' => '2025-01-01 08:00:00',
        ]);

        $this->artisan('processing-steps:reorder')
            ->assertSuccessful();

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step2->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step1->id,
            'ordering' => 2,
        ]);

        $this->assertDatabaseHas('processing_steps', [
            'id' => $step3->id,
            'ordering' => 3,
        ]);
    }

    #[Test]
    public function itHandlesExceptionDuringReorder(): void
    {
        $decision = Decision::factory()->create();

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-01',
        ]);

        $databaseManagerMock = $this->mock(DatabaseManager::class);
        $this->app->instance(DatabaseManager::class, $databaseManagerMock);

        $databaseManagerMock->expects('transaction')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->artisan('processing-steps:reorder')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function itLogsWarningOnFailedDecisions(): void
    {
        $decision = Decision::factory()->create();

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
            'deadline_at' => '2025-01-01',
        ]);

        $loggerMock = $this->mock(LoggerInterface::class);
        $this->app->instance(LoggerInterface::class, $loggerMock);

        $loggerMock->expects('error')
            ->once();

        $loggerMock->expects('warning')
            ->once()
            ->with('Reordering completed with failures', $this->anything());

        $databaseManagerMock = $this->mock(DatabaseManager::class);
        $this->app->instance(DatabaseManager::class, $databaseManagerMock);

        $databaseManagerMock->expects('transaction')
            ->once()
            ->andThrow(new Exception('Database error'));

        $this->artisan('processing-steps:reorder')
            ->expectsOutputToContain('failed to reorder')
            ->assertExitCode(Command::FAILURE);
    }
}
