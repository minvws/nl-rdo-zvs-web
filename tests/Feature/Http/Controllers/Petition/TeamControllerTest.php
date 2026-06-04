<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Petition;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class TeamControllerTest extends FeatureTestCase
{
    public function testIndex(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::TEAM_READ)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX, ['department' => $department])
            ->assertOk()
            ->assertSee($team->name)
            ->assertViewIs('teams.index');
    }

    public function testIndexShowsOnlyDepartmentTeams(): void
    {
        $department1 = Department::factory()->create();
        $department2 = Department::factory()->create();

        $team1 = Team::factory()->recycle($department1)->create([
            'name' => 'Department 1 Team',
        ]);
        $team2 = Team::factory()->recycle($department2)->create([
            'name' => 'Department 2 Team',
        ]);

        $authUser = User::factory()
            ->withPermissionsAndDepartment($department1, Permission::TEAM_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser, true, $department1)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX, ['department' => $department1])
            ->assertOk()
            ->assertSee($team1->name)
            ->assertDontSee($team2->name);
    }

    public function testCreate(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE, ['department' => $department])
            ->assertOk()
            ->assertViewIs('teams.create');
    }

    public function testStore(): void
    {
        $department = Department::factory()->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissionsAndDepartment($department, Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser, true, $department)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE, ['department' => $department], [
                'name' => $name,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(Team::class, [
            'name' => $name,
        ]);
    }

    public function testEdit(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()
            ->recycle($department)
            ->create();

        $authUser = User::factory()->withPermissions(Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $team->id,
            ])
            ->assertOk()
            ->assertViewIs('teams.edit');
    }

    public function testUpdate(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()
            ->recycle($department)
            ->create();
        $name = $this->faker->name();

        $authUser = User::factory()->withPermissions(Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $team->id,
            ], [
                'name' => $name,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(
                RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX,
                [
                    'department' => $department,
                ],
            );

        $this->assertDatabaseHas(Team::class, [
            'name' => $name,
        ]);
    }

    public function testEditWithWrongIdThrowsException(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $this->faker->uuid(),
            ])
            ->assertNotFound();
    }

    public function testEditWithInvalidIdThrowsException(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::TEAM_WRITE)->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $this->faker->word(),
            ])
            ->assertNotFound();
    }

    public function testIndexRequiresTeamReadPermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_INDEX, ['department' => $department])
            ->assertForbidden();
    }

    public function testCreateRequiresTeamWritePermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE, ['department' => $department])
            ->assertForbidden();
    }

    public function testStoreRequiresTeamWritePermission(): void
    {
        $department = Department::factory()->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_CREATE, ['department' => $department], [
                'name' => $this->faker->name(),
                'active' => true,
            ])
            ->assertForbidden();
    }

    public function testEditRequiresTeamWritePermission(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()->recycle($department)->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->getByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $team->id,
            ])
            ->assertForbidden();
    }

    public function testUpdateRequiresTeamWritePermission(): void
    {
        $department = Department::factory()->create();
        $team = Team::factory()->recycle($department)->create();

        $authUser = User::factory()->fullyVerified()->create();
        $this->beUser($authUser)
            ->postByRoute(RouteName::DEPARTMENTS_ADMIN_TEAMS_EDIT, [
                'department' => $department,
                'team' => $team->id,
            ], [
                'name' => $this->faker->name(),
                'active' => true,
            ])
            ->assertForbidden();
    }
}
