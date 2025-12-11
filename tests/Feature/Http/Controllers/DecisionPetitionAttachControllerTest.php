<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Decision;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class DecisionPetitionAttachControllerTest extends FeatureTestCase
{
    #[Test]
    public function testAttachFormNotFound(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'decision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testAttachForm(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::DECISION_WRITE,
        )->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'decision' => Decision::factory()->recycle($department)->create(),
            ])
            ->assertStatus(200)
            ->assertViewIs('petition.decision.petition-attach');
    }

    #[Test]
    public function testAttach(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->create();
        $decision = Decision::factory()->recycle($department)->create();

        $user = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::DECISION_WRITE,
        )->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_DECISION_PETITION_ATTACH,
                [
                    'department' => $department,
                    'decision' => $decision,
                ],
                [
                    'number' => $petition->number,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }

    #[Test]
    public function testDetach(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create();

        DB::table('decision_petition')->insert([
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);

        $user = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_WRITE,
            Permission::DECISION_WRITE,
        )->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_DECISION_PETITION_DETACH, [
                'department' => $department,
                'decision' => $decision,
                'relatedPetition' => $petition,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(URL::route(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department,
                'decision' => $decision,
            ]));

        $this->assertDatabaseMissing('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }
}
