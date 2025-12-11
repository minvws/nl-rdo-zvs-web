<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\PetitionDeliverableType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionDeliverable;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionDeliverableControllerTest extends FeatureTestCase
{
    public function testCreate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverableType' => $this->faker->randomElement(PetitionDeliverableType::cases()),
            ])
            ->assertOk()
            ->assertViewIs('petition.petition-deliverable.create');
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionDeliverableType = $this->faker->randomElement(PetitionDeliverableType::cases());

        $deadlineAt = $this->faker->calendarDate();
        $description = $this->faker->sentence();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_STORE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverableType' => $petitionDeliverableType,
            ], [
                'deadline_at' => $deadlineAt->format('Y-m-d'),
                'description' => $description,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionDeliverable::class, [
            'type' => $petitionDeliverableType->value,
            'deadline_at' => $deadlineAt,
            'description' => $description,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $deadlineAt,
        ]);
    }

    public function testStoreWithValidationError(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionDeliverableType = $this->faker->randomElement(PetitionDeliverableType::cases());

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_CREATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverableType' => $petitionDeliverableType,
            ], [
                'description' => $this->faker->sentence(),
            ])
            ->assertSessionHasErrors('deadline_at')
            ->assertRedirect();
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petitionDeliverable = PetitionDeliverable::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_EDIT, [
                'department' => $department,
                'petition' => $petitionDeliverable->petition_id,
                'petitionDeliverable' => $petitionDeliverable,
            ])
            ->assertOk()
            ->assertViewIs('petition.petition-deliverable.edit');
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        $petitionDeliverable = PetitionDeliverable::factory()
            ->recycle($petition)
            ->create();

        $deadlineAt = $this->faker->calendarDate();
        $description = $this->faker->sentence();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverable' => $petitionDeliverable,
            ], [
                'deadline_at' => $deadlineAt->format('Y-m-d'),
                'description' => $description,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionDeliverable::class, [
            'id' => $petitionDeliverable->id,
            'deadline_at' => $deadlineAt,
            'description' => $description,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $deadlineAt,
        ]);
    }

    public function testUpdateWhenDeadlineAtIsLatest(): void
    {
        $deadlineAt = $this->faker->calendarDate();
        $description = $this->faker->sentence();

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        PetitionDeliverable::factory()
            ->recycle($petition)
            ->create([
                'deadline_at' => $deadlineAt,
            ]);
        $updatedPetitionDeliverable = PetitionDeliverable::factory()
            ->recycle($petition)
            ->create([
                'deadline_at' => $deadlineAt,
            ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverable' => $updatedPetitionDeliverable,
            ], [
                'deadline_at' => $deadlineAt->addDay()->format('Y-m-d'),
                'description' => $description,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionDeliverable::class, [
            'id' => $updatedPetitionDeliverable->id,
            'deadline_at' => $deadlineAt->addDay(),
            'description' => $description,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $deadlineAt->addDay(),
        ]);
    }

    public function testUpdateWhenDeadlineAtIsNotLatest(): void
    {
        $deadlineAt = $this->faker->calendarDate();
        $description = $this->faker->sentence();

        $department = Department::factory()->create();
        $petition = Petition::factory()->recycle($department)->create();
        PetitionDeliverable::factory()
            ->recycle($petition)
            ->create([
                'description' => 'foo',
                'deadline_at' => $deadlineAt,
            ]);
        $updatedPetitionDeliverable = PetitionDeliverable::factory()
            ->recycle($petition)
            ->create([
                'description' => 'bar',
                'deadline_at' => $deadlineAt->addDay(),
            ]);

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_UPDATE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverable' => $updatedPetitionDeliverable,
            ], [
                'deadline_at' => $deadlineAt->subDay()->format('Y-m-d'),
                'description' => $description,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionDeliverable::class, [
            'id' => $updatedPetitionDeliverable->id,
            'deadline_at' => $deadlineAt->subDay(),
            'description' => $description,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $deadlineAt,
        ]);
    }

    public function testDelete(): void
    {
        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $petitionDeliverable = PetitionDeliverable::factory()
            ->recycle($petition)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_DELETE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverable' => $petitionDeliverable,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_PETITIONS_SHOW,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
            );

        $this->assertDatabaseMissing(PetitionDeliverable::class, [
            'id' => $petitionDeliverable->id,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $petition->date_of_entry,
        ]);
    }

    public function testDeleteWithExisting(): void
    {
        $deadlineAt = $this->faker->calendarDate();

        $department = Department::factory()->create();
        $petition = Petition::factory()
            ->recycle($department)
            ->create();
        $petitionDeliverable = PetitionDeliverable::factory()
            ->recycle($petition)
            ->create();
        PetitionDeliverable::factory()
            ->recycle($petition)
            ->create([
                'deadline_at' => $deadlineAt,
            ]);

        $authUser = User::factory()->withPermissions(Permission::PETITION_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_PETITIONS_PETITION_DELIVERABLE_DELETE, [
                'department' => $department,
                'petition' => $petition,
                'petitionDeliverable' => $petitionDeliverable,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_PETITIONS_SHOW,
                [
                    'department' => $department,
                    'petition' => $petition->id,
                ],
            );

        $this->assertDatabaseMissing(PetitionDeliverable::class, [
            'id' => $petitionDeliverable->id,
        ]);
        $this->assertDatabaseHas(Petition::class, [
            'id' => $petition->id,
            'deadline_at' => $deadlineAt,
        ]);
    }
}
