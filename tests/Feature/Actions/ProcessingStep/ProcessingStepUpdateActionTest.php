<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\ProcessingStep;

use App\Actions\ProcessingStep\ProcessingStepUpdateAction;
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

class ProcessingStepUpdateActionTest extends FeatureTestCase
{
    #[Test]
    public function updateAllFieldsSuccessfully(): void
    {
        $decision = Decision::factory()->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create([
                'name' => 'Original Name',
                'deadline_at' => CalendarDate::today(),
                'status' => ProcessingStepStatus::PENDING,
            ]);

        $user = User::factory()->create();
        $assignedUser = User::factory()->create();
        $newDeadline = CalendarDate::today()->addDays(10);

        $updateData = [
            'name' => 'Updated Name',
            'deadline_at' => $newDeadline->format('Y-m-d'),
            'status' => ProcessingStepStatus::CLOSED->value,
            'first_assignee' => $assignedUser->id,
        ];

        // Execute the action
        $action = new ProcessingStepUpdateAction($this->app->make(DatabaseManager::class));
        $action->execute($processingStep, $user, $updateData);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $processingStep->id,
            'name' => 'Updated Name',
            'status' => ProcessingStepStatus::CLOSED->value,
        ]);

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'processing_step_id' => $processingStep->id,
            'user_id' => $assignedUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);
    }

    #[Test]
    public function updateWithSecondaryAssignee(): void
    {
        $decision = Decision::factory()->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create([
                'name' => 'Original Name',
                'deadline_at' => CalendarDate::today(),
                'status' => ProcessingStepStatus::PENDING,
            ]);

        $user = User::factory()->create();
        $firstAssignee = User::factory()->create();
        $secondAssignee = User::factory()->create();
        $newDeadline = CalendarDate::today()->addDays(10);

        $updateData = [
            'name' => 'Updated Name',
            'deadline_at' => $newDeadline->format('Y-m-d'),
            'status' => ProcessingStepStatus::CLOSED->value,
            'first_assignee' => $firstAssignee->id,
            'second_assignee' => $secondAssignee->id,
        ];

        $action = new ProcessingStepUpdateAction($this->app->make(DatabaseManager::class));
        $action->execute($processingStep, $user, $updateData);

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'processing_step_id' => $processingStep->id,
            'user_id' => $firstAssignee->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $this->assertDatabaseHas(ProcessingStepAssignment::class, [
            'processing_step_id' => $processingStep->id,
            'user_id' => $secondAssignee->id,
            'assignment_role' => AssignmentRole::SECONDARY,
        ]);
    }
}
