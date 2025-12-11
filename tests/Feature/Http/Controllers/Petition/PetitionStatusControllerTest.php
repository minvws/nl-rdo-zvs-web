<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionStatusControllerTest extends FeatureTestCase
{
    public function testEdit(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $petition->department,
            Permission::PETITION_WRITE,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $petition->department)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertOk()
            ->assertViewIs('petition.change-status.edit');
    }

    public function testEditNotFound(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testUpdate(): void
    {
        $department = Department::factory()
            ->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create(['assigned_to' => null]);

        $status = PetitionStatus::factory()
            ->recycle($department)
            ->create();
        $date = $this->faker->calendarDate();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_UPDATE,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
                [
                    'petition_status_id' => $status->id->toString(),
                    'petition_status_date' => $date->toDateString(),
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        $this->assertEquals($status->id, $petition->petitionStatus->id);

        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_id' => $petition->id,
            'petition_status_id' => $petition->petitionStatus->id,
            'date' => $date->toDateString(),
        ]);

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_status_id' => $status->id,
        ]);
    }

    public function testUpdateNotFound(): void
    {
        $petition = Petition::factory()
            ->create(['assigned_to' => null]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(
                RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_UPDATE,
                [
                    'department' => $petition->department->slug,
                    'petition' => $petition->id,
                ],
                [
                    'petition_status_id' => $this->faker->uuid()->toString(),
                    'petition_status_date' => $this->faker->calendarDate()->toDateString(),
                ],
            )
            ->assertNotFound();
    }

    public function testEditStatusNotFound(): void
    {
        $department = Department::factory()
            ->create();
        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }
}
