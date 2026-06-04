<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Decision;

use App\Enums\Authorization\Permission;
use App\Enums\DecisionType;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class DecisionTeamControllerTest extends FeatureTestCase
{
    public function testStoreDecisionWithTeamAssignment(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_DECISIONS_STORE,
                ['department' => $department],
                [
                    'name' => 'Test Decision with Team',
                    'reference' => 'ref-001',
                    'date' => '2024-01-15',
                    'type' => DecisionType::REGULAR->value,
                    'team_id' => $team->id->toString(),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Decision::class, [
            'name' => 'Test Decision with Team',
            'team_id' => $team->id,
        ]);
    }

    public function testUpdateDecisionTeam(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()
            ->recycle($department)
            ->create(['team_id' => null]);
        $team = Team::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_DECISIONS_UPDATE,
                [
                    'department' => $department,
                    'decision' => $decision,
                ],
                [
                    'name' => $decision->name,
                    'reference' => $decision->reference,
                    'team_id' => $team->id->toString(),
                ],
            )
            ->assertSessionHasNoErrors();

        $decision->refresh();
        $this->assertEquals($team->id, $decision->team_id);
    }

    public function testCannotAssignTeamFromDifferentDepartment(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $team = Team::factory()
            ->recycle($department2)
            ->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department1)
            ->postByRoute(
                RouteName::DEPARTMENTS_DECISIONS_STORE,
                ['department' => $department1],
                [
                    'name' => 'Test Decision',
                    'reference' => 'ref-002',
                    'type' => DecisionType::REGULAR->value,
                    'team_id' => $team->id->toString(),
                ],
            )
            ->assertSessionHasErrors('team_id');
    }

    public function testDecisionTeamCanBeNull(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::DECISION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_DECISIONS_STORE,
                ['department' => $department],
                [
                    'name' => 'Test Decision without Team',
                    'reference' => 'ref-003',
                    'type' => DecisionType::REGULAR->value,
                    'team_id' => null,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Decision::class, [
            'name' => 'Test Decision without Team',
            'team_id' => null,
        ]);
    }
}
