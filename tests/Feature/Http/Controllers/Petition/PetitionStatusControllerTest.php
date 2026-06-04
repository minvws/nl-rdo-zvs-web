<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionStatus;
use App\Models\PetitionStatusHistory;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

use function now;
use function today;

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
            ->create();

        $status = PetitionStatus::factory()
            ->recycle($department)
            ->create();
        $dateString = today()->format('Y-m-d');

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
                    'petition_status_date' => $dateString,
                ],
            )
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $petition->id,
            ]);

        $petition->refresh();
        $this->assertEquals($status->id, $petition->petitionStatus->id);

        $this->assertDatabaseHas(PetitionStatusHistory::class, [
            'petition_id' => $petition->id,
            'petition_status_id' => $petition->petitionStatus->id,
            'date' => $dateString,
        ]);

        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'petition_status_id' => $status->id,
        ]);
    }

    public function testUpdateNotFound(): void
    {
        $petition = Petition::factory()
            ->create();

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
                    'petition_status_date' => today()->format('Y-m-d'),
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

    public function testDeleteHistory(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $statusA = PetitionStatus::factory()->recycle($department)->create();
        $statusB = PetitionStatus::factory()->recycle($department)->create();

        $petition->update(['petition_status_id' => $statusB->id]);

        PetitionStatusHistory::factory()->for($petition)->for($statusA)->create([
            'date' => now()->subDays(2)->format('Y-m-d'),
        ]);
        $historyB = PetitionStatusHistory::factory()->for($petition)->for($statusB)->create([
            'date' => now()->subDay()->format('Y-m-d'),
        ]);

        $petition->update(['petition_status_id' => $statusB->id]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department);

        $petition->refresh();

        $this->assertEquals($statusB->id, $petition->petition_status_id);

        $this->deleteByRoute(
            RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_DESTROY,
            [
                'department' => $department,
                'petition' => $petition->id,
                'petitionStatusHistory' => $historyB->id,
            ],
        )
            ->assertRedirectToRoute(RouteName::DEPARTMENTS_PETITIONS_CHANGE_STATUS_EDIT, [
                'department' => $department,
                'petition' => $petition->id,
            ])
            ->assertSessionHasNoErrors();

        $petition->refresh();
        $this->assertEquals($statusA->id, $petition->petition_status_id);
        $this->assertModelMissing($historyB);

        $timelineEntry = $petition->timelineItems()
            ->where('type', TimelineType::STATUS_OCCURRENCE)
            ->latest()
            ->first();
        $this->assertNotNull($timelineEntry);
        $this->assertEquals('Status verwijderd', $timelineEntry->data['comment']);
    }
}
