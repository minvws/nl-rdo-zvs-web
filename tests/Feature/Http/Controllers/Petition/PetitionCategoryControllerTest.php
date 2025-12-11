<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\PetitionCategory;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionCategoryControllerTest extends FeatureTestCase
{
    public function testIndex(): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($petitionCategory->name)
            ->assertViewIs('petition-categories.index');
    }

    public function testIndexShowsOnlyDepartmentPetitionCategories(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $petitionCategory1 = PetitionCategory::factory()->recycle($department1)->create([
            'name' => 'Department 1 Category',
        ]);
        $petitionCategory2 = PetitionCategory::factory()->recycle($department2)->create([
            'name' => 'Department 2 Category',
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::PETITION_CATEGORY_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX, ['department' => $department1])
            ->assertOk()
            ->assertSee($petitionCategory1->name)
            ->assertDontSee($petitionCategory2->name);
    }

    public function testCreate(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_CREATE, ['department' => $department])
            ->assertOk()
            ->assertViewIs('petition-categories.create');
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissionsAndDepartment(
            $department,
            Permission::PETITION_CATEGORY_WRITE,
        )->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_CREATE, ['department' => $department], [
                'name' => $name,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(PetitionCategory::class, [
            'name' => $name,

        ]);
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, [
                'department' => $department,
                'petitionCategory' => $petitionCategory->id,
            ])
            ->assertOk()
            ->assertViewIs('petition-categories.edit');
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $petitionCategory = PetitionCategory::factory()
            ->recycle($department)
            ->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, [
                'department' => $department,
                'petitionCategory' => $petitionCategory->id,
            ], [
                'name' => $name,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_INDEX,
                [
                    'department' => $department,
                ],
            );

        $this->assertDatabaseHas(PetitionCategory::class, [
            'name' => $name,
        ]);
    }

    public function testEditWithWrongIdThrowsException(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, [
                'department' => $department,
                'petitionCategory' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testEditWithInvalidIdThrowsException(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::PETITION_CATEGORY_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_PETITION_CATEGORIES_EDIT, [
                'department' => $department,
                'petitionCategory' => $this->faker->word(),
            ])
            ->assertNotFound();
    }
}
