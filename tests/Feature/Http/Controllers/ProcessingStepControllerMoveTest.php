<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepControllerMoveTest extends FeatureTestCase
{
    #[Test]
    public function moveUpViaRouteSuccessfully(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $secondStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_UP, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $secondStep,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]);

        // Verify orderings were swapped
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $secondStep->id,
            'ordering' => 1,
        ]);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $firstStep->id,
            'ordering' => 2,
        ]);
    }

    #[Test]
    public function moveDownViaRouteSuccessfully(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $firstStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 1]);

        $secondStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create(['ordering' => 2]);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_DOWN, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $firstStep,
            ])
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]);

        // Verify orderings were swapped
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $firstStep->id,
            'ordering' => 2,
        ]);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $secondStep->id,
            'ordering' => 1,
        ]);
    }

    #[Test]
    public function moveRequiresDecisionWritePermission(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create();

        $authUser = User::factory()->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_UP, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $processingStep,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function moveUpPreservesOtherStepsOrdering(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $step1 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 1]);
        $step2 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 2]);
        $step3 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 3]);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        // Move step 3 up (swap with step 2)
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_UP, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $step3,
            ]);

        // Verify step1 remained unchanged
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step1->id,
            'ordering' => 1,
        ]);

        // Verify step2 and step3 swapped
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step2->id,
            'ordering' => 3,
        ]);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step3->id,
            'ordering' => 2,
        ]);
    }

    #[Test]
    public function moveDownPreservesOtherStepsOrdering(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $step1 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 1]);
        $step2 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 2]);
        $step3 = ProcessingStep::factory()->recycle($decision)->create(['ordering' => 3]);

        $authUser = User::factory()
            ->withPermissions(Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        // Move step 1 down (swap with step 2)
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_MOVE_DOWN, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $step1,
            ]);

        // Verify step1 and step2 swapped
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step1->id,
            'ordering' => 2,
        ]);

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step2->id,
            'ordering' => 1,
        ]);

        // Verify step3 remained unchanged
        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $step3->id,
            'ordering' => 3,
        ]);
    }
}
