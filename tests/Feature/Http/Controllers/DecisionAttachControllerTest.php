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
use Str;
use Tests\Feature\FeatureTestCase;

class DecisionAttachControllerTest extends FeatureTestCase
{
    #[Test]
    public function testAttachFormNotFound(): void
    {
        $authUser = User::factory()->withPermissionsAndDepartment(null, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, null)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH_FORM, [
                'department' => $this->faker->slug(),
                'petition' => $this->faker->uuid(),
                'decision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testAttachForm(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertStatus(200)
            ->assertViewIs('petition.decision.attach');
    }

    #[Test]
    public function testAttach(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->create([
            'reference' => $this->faker->word(),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH, [
                'department' => $department,
                'petition' => $petition,
                'reference' => $decision->reference,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }

    #[Test]
    public function testAttachIsCaseInsensitiveReference(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->create([
            'reference' => 'ref-case123',
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)
            ->fullyVerified()
            ->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    // send upper-cased reference to ensure controller lower-cases input
                    'reference' => Str::upper($decision->reference),
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
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create([
            'reference' => $this->faker->word(),
        ]);

        DB::table('decision_petition')->insert([
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, [
                'department' => $department,
                'petition' => $petition,
                'relatedDecision' => $decision,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(URL::route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition,
            ]));

        $this->assertDatabaseMissing('decision_petition', [
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);
    }

    #[Test]
    public function testDetachWithReferer(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $decision = Decision::factory()->recycle($department)->hasAttached($petition)->create([
            'reference' => $this->faker->word(),
        ]);

        DB::table('decision_petition')->insert([
            'decision_id' => $decision->id,
            'petition_id' => $petition->id,
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, [
                'department' => $department,
                'petition' => $petition,
                'relatedDecision' => $decision,
                'referer' => 'decision',
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

    #[Test]
    public function testDetachNonExistingDecision(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, [
                'department' => $department,
                'petition' => $petition,
                'relatedDecision' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testAttachToNonExistingPetition(): void
    {
        $department = Department::factory()->create();
        $decision = Decision::factory()->create([
            'reference' => $this->faker->word(),
        ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_ATTACH, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
                'reference' => $decision->reference,
            ])
            ->assertNotFound();
    }
}
