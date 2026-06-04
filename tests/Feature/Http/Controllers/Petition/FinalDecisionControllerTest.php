<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\DecisionPetition;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class FinalDecisionControllerTest extends FeatureTestCase
{
    #[Test]
    public function testEditShowsFormWithDecisions(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $decision = Decision::factory()->for($department)->create();
        $petition->decisions()->attach($decision);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200)
            ->assertViewIs('petition.final-decision');
    }

    #[Test]
    public function testEditRequiresUpdatePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_EDIT, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function testUpdateSetsADecisionAsFinal(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $decision = Decision::factory()->for($department)->create();
        $petition->decisions()->attach($decision);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_UPDATE,
                ['department' => $department, 'petition' => $petition],
                ['final_decision_id' => $decision->id->toString()],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect(URL::route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $this->assertDatabaseHas('decision_petition', [
            'petition_id' => $petition->id,
            'decision_id' => $decision->id,
            'is_final' => true,
        ]);
    }

    #[Test]
    public function testUpdateClearsFinalDecisionWhenNullSubmitted(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $decision = Decision::factory()->for($department)->create();
        $petition->decisions()->attach($decision);

        // Mark it as final first
        DecisionPetition::query()
            ->where('petition_id', $petition->id)
            ->where('decision_id', $decision->id)
            ->update(['is_final' => true]);

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_UPDATE,
                ['department' => $department, 'petition' => $petition],
                ['final_decision_id' => ''], // "Geen van deze besluiten is finaal"
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('decision_petition', [
            'petition_id' => $petition->id,
            'decision_id' => $decision->id,
            'is_final' => false,
        ]);
    }

    #[Test]
    public function testUpdateRejectsDecisionNotLinkedToPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();
        $otherDecision = Decision::factory()->for($department)->create(); // NOT attached

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_UPDATE,
                ['department' => $department, 'petition' => $petition],
                ['final_decision_id' => $otherDecision->id->toString()],
            )
            ->assertSessionHasErrors('final_decision_id');
    }

    #[Test]
    public function testUpdateRequiresUpdatePermission(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->for($department)->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_FINAL_DECISION_UPDATE,
                ['department' => $department, 'petition' => $petition],
                ['final_decision_id' => null],
            )
            ->assertStatus(403);
    }
}
