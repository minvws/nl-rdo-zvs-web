<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\ProcessingStepStatus;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ProcessingStepControllerTest extends FeatureTestCase
{
    #[Test]
    public function createDisplaysFormCorrectly(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_CREATE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertOk()
            ->assertViewIs('departments.decisions.processing-steps.create')
            ->assertSee('name')
            ->assertSee('deadline_at')
            ->assertSee('status');
    }

    #[Test]
    public function createRequiresDecisionWritePermission(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->fullyVerified()->create(); // User without specific permission
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_CREATE, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertForbidden();
    }

    #[Test]
    public function storeCreatesProcessingStep(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $user = User::factory()->create();

        $stepName = $this->faker->sentence(3);
        $deadline = $this->faker->optional()->date();
        $status = ProcessingStepStatus::PENDING->value;

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_STORE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => $stepName,
                'decision_id' => $decision->id->toString(),
                'deadline_at' => $deadline,
                'status' => $status,
                'assigned_to' => $user->id->toString(),
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertSessionHas('message.success');

        $this->assertDatabaseHas(ProcessingStep::class, [
            'name' => $stepName,
            'deadline_at' => $deadline,
            'status' => $status,
            'assigned_to' => $user->id,
            'decision_id' => $decision->id,
        ]);
    }

    #[Test]
    public function storeValidatesRequiredFields(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_STORE, [
                'department' => $department,
                'decision' => $decision,
            ], [
                'name' => '',
                'deadline_at' => '',
                'status' => '',
            ])
            ->assertSessionHasErrors(['name', 'status']);
    }

    #[Test]
    public function editDisplaysFormWithProcessingStepData(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create([
                'deadline_at' => $this->faker->calendarDate(),
            ]);

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_EDIT, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $processingStep,
            ])
            ->assertOk()
            ->assertViewIs('departments.decisions.processing-steps.edit')
            ->assertSee($processingStep->name)
            ->assertSee($processingStep->deadline_at->format('Y-m-d'));
    }

    #[Test]
    public function updateModifiesProcessingStep(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create();
        $user = User::factory()->create();

        $updatedName = $this->faker->sentence(3);
        $updatedDeadline = $this->faker->optional()->date();
        $updatedStatus = ProcessingStepStatus::CLOSED->value;

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_UPDATE, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $processingStep,
            ], [
                'name' => $updatedName,
                'deadline_at' => $updatedDeadline,
                'status' => $updatedStatus,
                'assigned_to' => $user->id->toString(), // Using UUID string instead of ID
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertSessionHas('message.success');

        $this->assertDatabaseHas(ProcessingStep::class, [
            'id' => $processingStep->id,
            'name' => $updatedName,
            'deadline_at' => $updatedDeadline,
            'status' => $updatedStatus,
            'assigned_to' => $user->id,
        ]);
    }

    #[Test]
    public function deleteRemovesProcessingStep(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();
        $processingStep = ProcessingStep::factory()
            ->recycle($decision)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::DECISION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_DELETE, [
                'department' => $department,
                'decision' => $decision,
                'processingStep' => $processingStep,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ])
            ->assertSessionHas('message.success');

        $this->assertDatabaseMissing(ProcessingStep::class, [
            'id' => $processingStep->id,
        ]);
    }

    #[Test]
    public function testProcessingStepCrossDepartmentVulnerability(): void
    {
        $departmentA = Department::factory()->create();
        $departmentB = Department::factory()->create();

        $decisionFromDepartmentA = Decision::factory()
            ->recycle($departmentA)
            ->create();

        $processingStep = ProcessingStep::factory()
            ->recycle($decisionFromDepartmentA)
            ->create(['name' => 'Secret Processing Step']);

        $userFromDepartmentB = User::factory()
            ->withPermissionsAndDepartment($departmentB, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $response = $this->beUser($userFromDepartmentB, true, $departmentB)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_PROCESSING_STEPS_EDIT, [
                'department' => $departmentB->slug,
                'decision' => $decisionFromDepartmentA->id,
                'processingStep' => $processingStep->id,
            ]);

        $response->assertNotFound();
    }
}
