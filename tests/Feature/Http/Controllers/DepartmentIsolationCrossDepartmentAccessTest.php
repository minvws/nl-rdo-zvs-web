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
use Tests\Feature\FeatureTestCase;

class DepartmentIsolationCrossDepartmentAccessTest extends FeatureTestCase
{
    public function testDecisionShowIntentionallyDisplaysCrossDepartmentPetitions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $decision = Decision::factory()->recycle($department1)->create();

        $petition1 = Petition::factory()->recycle($department1)->create([
            'name' => 'Department 1 Petition',
        ]);
        $petition2 = Petition::factory()->recycle($department2)->create([
            'name' => 'Department 2 Petition - Should Be Visible',
        ]);

        $decision->petitions()->attach([$petition1->id, $petition2->id]);

        $user = User::factory()->withPermissionsAndDepartment($department1, Permission::DECISION_READ)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_DECISIONS_SHOW, [
                'department' => $department1->slug,
                'decision' => $decision,
            ]);

        $response->assertOk()
            ->assertSee('Department 1 Petition')
            ->assertSee('Department 2 Petition - Should Be Visible');
    }

    public function testPetitionShowIntentionallyDisplaysCrossDepartmentDecisions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition = Petition::factory()->recycle($department1)->create();

        $decision1 = Decision::factory()->recycle($department1)->create([
            'name' => 'Department 1 Decision',
        ]);
        $decision2 = Decision::factory()->recycle($department2)->create([
            'name' => 'Department 2 Decision - Should Be Visible',
        ]);

        $petition->decisions()->attach([$decision1->id, $decision2->id]);

        $user = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_READ)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department1->slug,
                'petition' => $petition,
            ]);

        $response->assertOk()
            ->assertSee('Department 1 Decision')
            ->assertSee('Department 2 Decision - Should Be Visible');
    }

    public function testUserCanDetachPetitionFromOtherDepartmentEvenWithoutWritePermissions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($department1)->create(['name' => 'Petition Department 1']);
        $petition2 = Petition::factory()->recycle($department2)->create(['name' => 'Petition Department 2']);

        DB::table('petition_petition')->insert([
            'petition_id' => $petition1->id,
            'related_petition_id' => $petition2->id,
        ]);

        $user = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_WRITE)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department1)
            ->postByRoute(RouteName::DEPARTMENTS_PETITION_PETITION_DETACH, [
                'department' => $department1->slug,
                'petition' => $petition1,
                'relatedPetition' => $petition2,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseMissing('petition_petition', [
            'petition_id' => $petition1->id,
            'related_petition_id' => $petition2->id,
        ]);
    }

    public function testUserCanAttachPetitionFromOtherDepartmentEvenWithoutWritePermissions(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition1 = Petition::factory()->recycle($department1)->create(['name' => 'Petition Department 1']);
        $petition2 = Petition::factory()->recycle($department2)->create(['name' => 'Petition Department 2']);

        $user = User::factory()->withPermissionsAndDepartment($department1, Permission::PETITION_WRITE)->fullyVerified()->create();

        $response = $this->beUser($user, true, $department1)
            ->postByRoute(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH, [
                'department' => $department1->slug,
                'petition' => $petition1,
            ], [
                'number' => $petition2->number,
            ]);

        $response->assertStatus(302);

        $this->assertDatabaseHas('petition_petition', [
            'petition_id' => $petition1->id,
            'related_petition_id' => $petition2->id,
        ]);
    }

    public function testRelatedDecisionRouteBindingRespectsUserDepartment(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petition = Petition::factory()->recycle($department1)->create();
        $decision = Decision::factory()->recycle($department2)->create();

        $user = User::factory()->withPermissionsAndDepartment(
            $department1,
            Permission::DECISION_WRITE,
            Permission::PETITION_WRITE,
        )->fullyVerified()->create();

        $response = $this->beUser($user, true, $department1)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_DECISION_DETACH, [
                'department' => $department1,
                'petition' => $petition,
                'relatedDecision' => $decision,
            ]);

        $response->assertStatus(302);
    }
}
