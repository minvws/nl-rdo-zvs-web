<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use App\Models\UserGlobalRole;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class UserControllerTest extends FeatureTestCase
{
    public function testCreate(): void
    {
        Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::USER_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_USER_CREATE)
            ->assertOk()
            ->assertViewIs('user.create');
    }

    public function testEdit(): void
    {
        Department::factory()->create();
        $user = User::factory()->create();

        $authUser = User::factory()
            ->fullyVerified()
            ->withPermissions(Permission::USER_WRITE)
            ->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user])
            ->assertOk()
            ->assertViewIs('user.edit');
    }

    public function testEditNotFound(): void
    {
        $authUser = User::factory()
            ->withPermissions(Permission::USER_WRITE)
            ->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_USER_EDIT, ['user' => $this->faker->uuid()])
            ->assertNotFound();
    }

    public function testIndex(): void
    {
        Department::factory()->create();

        $authUser = User::factory()
            ->withPermissions(Permission::USER_READ)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->getByRoute(RouteName::ADMIN_USER_INDEX)
            ->assertOk()
            ->assertViewIs('user.index');
    }

    #[Test]
    public function testResetOtp(): void
    {
        Department::factory()->create();
        $user = User::factory()
            ->fullyVerified()
            ->create();

        $authUser = User::factory()
            ->withPermissions(Permission::USER_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_OTP_RESET, ['user' => $user])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user]);

        $user->refresh();
        $this->assertNull($user->otp_secret);
        $this->assertNull($user->otp_verified_at);
    }

    public function testStore(): void
    {
        $name = $this->faker->name();
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $authUser = User::factory()
            ->withPermissions(Permission::USER_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_STORE, data: [
                'name' => $name,
                'email' => $email,
                'active' => true,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas(User::class, [
            'name' => $name,
            'email' => $email,
        ]);
    }

    public function testStoreWithGlobalRole(): void
    {
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        /** @var GlobalRole $globalRole */
        $globalRole = $this->faker->randomElement(GlobalRole::cases());

        $authUser = User::factory()
            ->withPermissions(Permission::USER_WRITE)
            ->fullyVerified()
            ->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_STORE, data: [
                'name' => $this->faker->name(),
                'email' => $email,
                'active' => true,
                'global_roles' => [$globalRole->value],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $id = User::query()->whereEmail($email)->value('id');

        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $id,
            'role' => $globalRole,
        ]);
    }

    public function testStoreWithDepartmentRole(): void
    {
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        /** @var DepartmentRole $departmentRole */
        $departmentRole = $this->faker->randomElement(DepartmentRole::cases());
        $department = Department::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_STORE, data: [
                'name' => $this->faker->name(),
                'email' => $email,
                'active' => true,
                'department_roles' => [$department->id->toString() => [$departmentRole->value]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user = User::query()->whereEmail($email)->first();

        $this->assertDatabaseHas(DepartmentUser::class, [
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => $departmentRole,
        ]);
    }

    public function testUpdate(): void
    {
        $user = User::factory()->create();

        $name = $this->faker->name();
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, ['user' => $user], [
                'name' => $name,
                'email' => $email,
                'active' => true,
                'roles' => [GlobalRole::ADMINISTRATOR->value],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user]);

        $result = $this->getUserById($user->id);
        $this->assertSame($name, $result->name);
        $this->assertSame($email, $result->email);
    }

    public function testUpdateWithGlobalRole(): void
    {
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        /** @var GlobalRole $globalRole */
        $globalRole = $this->faker->randomElement(GlobalRole::cases());
        $user = User::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, ['user' => $user], [
                'name' => $this->faker->name(),
                'email' => $email,
                'active' => true,
                'global_roles' => [$globalRole->value],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user]);

        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => $globalRole,
        ]);
    }

    public function testUpdateWithDepartmentRole(): void
    {
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        /** @var DepartmentRole $departmentRole */
        $departmentRole = $this->faker->randomElement(DepartmentRole::cases());
        $department = Department::factory()->create();

        $user = User::factory()->create();

        DepartmentUser::factory()->create([
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => $departmentRole,
        ]);

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, [$user->id], [
                'name' => $this->faker->name(),
                'email' => $email,
                'active' => true,
                'department_roles' => [$department->id->toString() => [$departmentRole->value]],
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user]);

        $this->assertDatabaseHas(DepartmentUser::class, [
            'department_id' => $department->id,
            'user_id' => $user->id,
            'role' => $departmentRole,
        ]);
    }

    public function testUpdateNotFound(): void
    {
        $id = $this->faker->uuid();

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, ['user' => $id], [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'roles' => [GlobalRole::ADMINISTRATOR->value],
            ])
            ->assertNotFound();
    }

    public function testUpdateWithNotAllowedEmailAddress(): void
    {
        Config::set('auth.allowed_user_email_domains', '');

        $user = User::factory()->create();

        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, [$user->id], [
                'name' => $this->faker->name(),
                'email' => $this->faker->unique()->safeEmail(),
                'department_roles' => [],
            ])
            ->assertSessionHasErrors(['email'])
            ->assertRedirectBack();
    }

    public function testUpdateRemoveGlobalRole(): void
    {
        $email = $this->faker->unique()->safeEmail();
        Config::set('auth.allowed_user_email_domains', $email);

        // Create a user with administrator role
        $user = User::factory()->create();

        // Assign administrator role to the user
        UserGlobalRole::create([
            'id' => $this->faker->uuid(),
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Verify the role is assigned
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Update the user to remove the global role (empty global_roles array)
        $authUser = User::factory()->withPermissions(Permission::USER_WRITE)->fullyVerified()->create();

        $this->beUser($authUser)
            ->postByRoute(RouteName::ADMIN_USER_UPDATE, ['user' => $user], [
                'name' => $this->faker->name(),
                'email' => $email,
                'active' => true,
                'global_roles' => [], // Remove all global roles
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirectToRoute(RouteName::ADMIN_USER_EDIT, ['user' => $user]);

        // Verify the role has been removed
        $this->assertDatabaseMissing(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);
    }
}
