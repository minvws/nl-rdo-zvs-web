<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class PetitionAttachControllerTest extends FeatureTestCase
{
    #[Test]
    public function testShowAttachForm(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();

        $user = User::factory()->withPermissionsAndDepartment($petition->department, Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user, true, $petition->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $petition,
            ])
            ->assertOk()
            ->assertViewIs('petition.petition.attach');
    }

    #[Test]
    public function testShowAttachFormWithInvalidPetitionId(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->getByRoute(RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH_FORM, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    #[Test]
    public function testAttachPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $relatedPetition = Petition::factory()->create();

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();

        $this->beUser($user)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITION_PETITION_ATTACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                ],
                [
                    'number' => $relatedPetition->number,
                ],
            )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $this->assertDatabaseHas('petition_petition', [
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);
    }

    #[Test]
    public function testDetachPetition(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $relatedPetition = Petition::factory()->create();

        DB::table('petition_petition')->insert([
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);

        $user = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();

        $response = $this->beUser($user)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITION_PETITION_DETACH,
                [
                    'department' => $department,
                    'petition' => $petition,
                    'relatedPetition' => $relatedPetition,
                ],
            );

        $response->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
            'department' => $department,
            'petition' => $petition,
        ]);

        $this->assertDatabaseMissing('petition_petition', [
            'petition_id' => $petition->id,
            'related_petition_id' => $relatedPetition->id,
        ]);
    }
}
