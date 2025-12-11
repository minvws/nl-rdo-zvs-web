<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Factories;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Enums\Authorization\Permission;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use App\Models\UserGlobalRole;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function config;

class UserFactoryTest extends FeatureTestCase
{
    #[Test]
    public function testWithGlobalRole(): void
    {
        $user = User::factory()
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);
    }

    #[Test]
    public function testCombinationUnverifiedWithOtpDisabled(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->unverifiedEmail()
            ->otpDisabled()
            ->create();

        $this->assertNull($user->email_verified_at);
        $this->assertNull($user->otp_secret);
        $this->assertNull($user->otp_verified_at);
        // But should still have password from fullyVerified()
        $this->assertNotNull($user->password);
    }

    #[Test]
    public function testCombinationWithHashedPasswordAndGlobalRole(): void
    {
        $hashedPassword = Hash::make('test-password-123');
        $user = User::factory()
            ->fullyVerified()
            ->withHashedPassword($hashedPassword)
            ->withGlobalRole(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->assertEquals($hashedPassword, $user->password);
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);
    }

    #[Test]
    public function testUnverifiedEmailMethod(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->unverifiedEmail()
            ->create();

        $this->assertNull($user->email_verified_at);
        // Should still have other verified properties
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->otp_verified_at);
        $this->assertNotNull($user->otp_secret);
    }

    #[Test]
    public function testFullyVerifiedMethod(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->create();

        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->otp_verified_at);
        $this->assertNotNull($user->otp_secret);
    }

    #[Test]
    public function testWithMultipleGlobalRoles(): void
    {
        // Note: Currently only ADMINISTRATOR exists, but this tests the method structure
        $user = User::factory()
            ->withGlobalRoles(GlobalRole::ADMINISTRATOR)
            ->create();

        $globalRoles = UserGlobalRole::query()
            ->where('user_id', $user->id)
            ->get();

        $this->assertCount(1, $globalRoles);
        $this->assertTrue($globalRoles->contains('role', GlobalRole::ADMINISTRATOR));
    }

    #[Test]
    public function testAsAdministrator(): void
    {
        $user = User::factory()
            ->asAdministrator()
            ->create();

        // Should be verified
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->otp_verified_at);
        $this->assertNotNull($user->otp_secret);

        // Should have administrator role
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);
    }

    #[Test]
    public function testWithLastVisitedDepartment(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->withLastVisitedDepartment($department)
            ->create();

        $this->assertEquals($department->id->toString(), $user->last_visited_department_id);
    }

    #[Test]
    public function testActive(): void
    {
        $activeUser = User::factory()
            ->active()
            ->create();

        $inactiveUser = User::factory()
            ->active(false)
            ->create();

        $this->assertTrue($activeUser->active);
        $this->assertFalse($inactiveUser->active);
    }

    #[Test]
    public function testInactive(): void
    {
        $user = User::factory()
            ->inactive()
            ->create();

        $this->assertFalse($user->active);
    }

    #[Test]
    public function testCombinedMethods(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->asAdministrator()
            ->withLastVisitedDepartment($department)
            ->withDepartmentRoles($department, DepartmentRole::WRITE)
            ->create();

        // Should be verified administrator
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Should have last visited department
        $this->assertEquals($department->id->toString(), $user->last_visited_department_id);

        // Should have department role
        $this->assertDatabaseHas(DepartmentUser::class, [
            'user_id' => $user->id,
            'department_id' => $department->id,
            'role' => DepartmentRole::WRITE,
        ]);
    }

    #[Test]
    public function testExistingMethodsStillWork(): void
    {
        $department = Department::factory()->create();

        // Test existing verified() method
        $verifiedUser = User::factory()
            ->fullyVerified()
            ->create();

        $this->assertNotNull($verifiedUser->email_verified_at);
        $this->assertNotNull($verifiedUser->password);

        // Test existing withGlobalRole() method
        $adminUser = User::factory()
            ->withGlobalRoles(GlobalRole::ADMINISTRATOR)
            ->create();

        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $adminUser->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Test existing withDepartmentRoles() method
        $userWithDeptRoles = User::factory()
            ->withDepartmentRoles($department, DepartmentRole::READ, DepartmentRole::WRITE)
            ->create();

        $this->assertDatabaseHas(DepartmentUser::class, [
            'user_id' => $userWithDeptRoles->id,
            'department_id' => $department->id,
            'role' => DepartmentRole::READ,
        ]);
    }

    #[Test]
    public function testWithPermissions(): void
    {
        $user = User::factory()
            ->withPermissions(Permission::USER_READ, Permission::USER_WRITE)
            ->fullyVerified()
            ->create();

        // Should be verified administrator
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Should have configured permissions in config
        $permissions = config('permissions.roles_and_permissions.' . GlobalRole::ADMINISTRATOR->value);
        $this->assertContains(Permission::USER_READ->value, $permissions);
        $this->assertContains(Permission::USER_WRITE->value, $permissions);
    }

    #[Test]
    public function testWithPermissionsAndDepartment(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::PETITION_READ)
            ->fullyVerified()
            ->create();

        // Should be administrator with permissions
        $this->assertDatabaseHas(UserGlobalRole::class, [
            'user_id' => $user->id,
            'role' => GlobalRole::ADMINISTRATOR,
        ]);

        // Should have last visited department set
        $this->assertEquals($department->id->toString(), $user->last_visited_department_id);

        // Should have configured permissions
        $permissions = config('permissions.roles_and_permissions.' . GlobalRole::ADMINISTRATOR->value);
        $this->assertContains(Permission::PETITION_READ->value, $permissions);
    }
}
