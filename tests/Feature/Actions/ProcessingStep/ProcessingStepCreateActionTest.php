<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepCreateAction;
use App\Enums\AssignmentRole;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\ProcessingStepAssignment;
use App\Models\User;
use App\ValueObjects\CalendarDate;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepCreateActionTest extends FeatureTestCase
{
    #[Test]
    public function testProcessingStepCreateAction(): void
    {
        $decision = Decision::factory()->create();
        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $deadlineDate = CalendarDate::today()->addDays(14);

        $data = [
            'name' => 'Complete Processing Step',
            'deadline_at' => $deadlineDate->format('Y-m-d'),
            'status' => ProcessingStepStatus::CLOSED->value,
            'first_assignee' => $assignedUser->id,
        ];

        $action = new ProcessingStepCreateAction(
            $this->app->make(DatabaseManager::class),
        );
        $action->execute($decision, $user, $data);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'name' => 'Complete Processing Step',
            'decision_id' => $decision->id,
            'status' => ProcessingStepStatus::CLOSED->value,
        ]);

        $processingStep = ProcessingStep::where('decision_id', $decision->id)->first();

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'user_id' => $assignedUser->id,
            'processing_step_id' => $processingStep->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);
    }

    #[Test]
    public function testProcessingStepCreateActionWithSecondaryAssignee(): void
    {
        $decision = Decision::factory()->create();
        $user = User::factory()->create();
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();
        $deadlineDate = CalendarDate::today()->addDays(14);

        $data = [
            'name' => 'Complete Processing Step',
            'deadline_at' => $deadlineDate->format('Y-m-d'),
            'status' => ProcessingStepStatus::PENDING->value,
            'first_assignee' => $firstAssignee->id,
            'second_assignee' => $secondAssignee->id,
        ];

        $action = new ProcessingStepCreateAction(
            $this->app->make(DatabaseManager::class),
        );
        $action->execute($decision, $user, $data);

        $processingStep = ProcessingStep::where('decision_id', $decision->id)->first();

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'user_id' => $firstAssignee->id,
            'processing_step_id' => $processingStep->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'user_id' => $secondAssignee->id,
            'processing_step_id' => $processingStep->id,
            'assignment_role' => AssignmentRole::SECONDARY,
        ]);
    }

    #[Test]
    public function createActionSetsCorrectOrdering(): void
    {
        $decision = Decision::factory()->create();
        $user = User::factory()->create();

        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 1,
        ]);
        ProcessingStep::factory()->create([
            'decision_id' => $decision->id,
            'ordering' => 3,
        ]);

        $data = [
            'name' => 'New Processing Step',
            'status' => ProcessingStepStatus::PENDING->value,
        ];

        $action = new ProcessingStepCreateAction(
            $this->app->make(DatabaseManager::class),
        );
        $action->execute($decision, $user, $data);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'name' => 'New Processing Step',
            'decision_id' => $decision->id,
            'ordering' => 4,
        ]);
    }

    #[Test]
    public function createActionSetsCorrectOrderingWhenNoExistingSteps(): void
    {
        $decision = Decision::factory()->create();
        $user = User::factory()->create();

        $data = [
            'name' => 'First Processing Step',
            'status' => ProcessingStepStatus::PENDING->value,
        ];

        $action = new ProcessingStepCreateAction(
            $this->app->make(DatabaseManager::class),
        );
        $action->execute($decision, $user, $data);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'name' => 'First Processing Step',
            'decision_id' => $decision->id,
            'ordering' => 1,
        ]);
    }
}
