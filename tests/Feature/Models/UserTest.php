<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\ProcessingStepStatus;
use App\Models\Decision;
use App\Models\Department;
use App\Models\ProcessingStep;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UserTest extends FeatureTestCase
{
    #[Test]
    public function testAssignedProcessingStepsRelationship(): void
    {
        $user = User::factory()->create();

        $processingStep = $user->assignedProcessingSteps()->create([
            'name' => 'Test Processing Step',
            'decision_id' => Decision::factory()->create()->id,
            'status' => ProcessingStepStatus::PENDING,
        ]);

        $this->assertInstanceOf(ProcessingStep::class, $processingStep);
        $this->assertEquals($user->id, $processingStep->assigned_to);
        $this->assertEquals(1, $user->assignedProcessingSteps->count());
    }

    #[Test]
    public function testAssignedProcessingStepsRelationshipEmpty(): void
    {
        $user = User::factory()->create();
        ProcessingStep::factory()->count(2)->create(); // Not assigned to our user

        $this->assertEquals(0, $user->assignedProcessingSteps->count());
    }

    #[Test]
    public function testDepartmentRelationship(): void
    {
        $role = $this->faker->randomElement(DepartmentRole::cases());

        $user = User::factory()->create();
        $department = Department::factory()->create();

        $user->departments()->attach($department, ['role' => $role]);

        $userFromRelation = $department->users()->first();

        $this->assertEquals($userFromRelation->pivot->role, $role);

        $this->assertDatabaseHas('department_user', [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => $role,
        ]);
    }
}
