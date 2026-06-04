<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\AssignmentRole;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\ProcessingStep;
use App\Models\ProcessingStepAssignment;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepTest extends FeatureTestCase
{
    #[Test]
    public function testAssignedUser(): void
    {
        $user = User::factory()->create();
        $decision = Decision::factory()->create();

        $processingStep = ProcessingStep::factory()->create([
            'name' => 'Test Processing Step',
            'decision_id' => $decision->id,
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $processingStep->assignments()->create([
            'user_id' => $user->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $this->assertInstanceOf(ProcessingStep::class, $processingStep);
        $this->assertEquals($user->id, $processingStep->firstAssignee->user->id);
    }

    #[Test]
    public function testSecondAssigneeRelationship(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $decision = Decision::factory()->create();

        $processingStep = ProcessingStep::factory()->create([
            'name' => 'Test Processing Step',
            'decision_id' => $decision->id,
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $processingStep->assignments()->create([
            'user_id' => $firstUser->id,
            'assignment_role' => AssignmentRole::PRIMARY,
        ]);

        $assignment = $processingStep->assignments()->create([
            'user_id' => $secondUser->id,
            'assignment_role' => AssignmentRole::SECONDARY,
        ]);

        $this->assertEquals($secondUser->id, $processingStep->secondAssignee->user_id);

        $loadedAssignment = ProcessingStepAssignment::find($assignment->id);
        $this->assertEquals($processingStep->id, $loadedAssignment->processingStep->id);
    }
}
