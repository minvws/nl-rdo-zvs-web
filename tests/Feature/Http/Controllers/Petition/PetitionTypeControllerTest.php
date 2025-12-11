<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\PetitionTypeType;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\PetitionStatus;
use App\Models\PetitionType;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

use function __;

class PetitionTypeControllerTest extends FeatureTestCase
{
    public function testIndex(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_TYPE_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($petitionType->name)
            ->assertSee(__('petition_type.active'))
            ->assertSee(__('general.yes'))
            ->assertViewIs('petition-types.index');
    }

    public function testIndexShowsOnlyDepartmentPetitionTypes(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionType1 = PetitionType::factory()->recycle($department1)->create([
            'name' => 'Department 1 Petition Type',
        ]);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create([
            'name' => 'Department 2 Petition Type',
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::PETITION_TYPE_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX, ['department' => $department1])
            ->assertOk()
            ->assertSee($petitionType1->name)
            ->assertDontSee($petitionType2->name);
    }

    public function testCreate(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_TYPE_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE, ['department' => $department])
            ->assertOk()
            ->assertViewIs('petition-types.create');
    }

    public function testStore(): void
    {
        $type = $this->faker->randomElement(PetitionTypeType::cases());
        $department = Department::factory()->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::PETITION_TYPE_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_CREATE, ['department' => $department], [
                'name' => $name,
                'type' => $type->value,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionType::class, [
            'name' => $name,
            'active' => true,
        ]);

        $petitionType = PetitionType::query()->where('name', $name)->first();

        $this->assertDatabaseHas(PetitionStatus::class, [
            'petition_type_id' => $petitionType->id->toString(),
        ]);
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_TYPE_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT, [
                'department' => $department,
                'petitionType' => $petitionType,
            ])
            ->assertOk()
            ->assertViewIs('petition-types.edit');
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petitionType = PetitionType::factory()
            ->recycle($department)
            ->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissions(Permission::PETITION_TYPE_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT, [
                'department' => $department,
                'petitionType' => $petitionType,
            ], [
                'name' => $name,
                'active' => false,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_INDEX,
                [
                    'department' => $department,
                ],
            );

        $this->assertDatabaseHas(PetitionType::class, [
            'name' => $name,
            'active' => false,
        ]);
    }

    public function testEditWithNotFoundException(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_TYPE_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_TYPES_EDIT, [
                'department' => $department,
                'petitionType' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }
}
