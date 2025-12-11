<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Enums\Authorization\Permission;
use App\Enums\RouteName;
use App\Models\Department;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;

class AdminPanelControllerTest extends FeatureTestCase
{
    #[Test]
    public function testAdminPanel(): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->withPermissionsAndDepartment(
                $department,
                Permission::ADMIN_PANEL_VIEW,
                Permission::USER_WRITE,
                Permission::PUBLIC_HOLIDAY_WRITE,
                Permission::PETITION_TYPE_WRITE,
            )
            ->fullyVerified()
            ->create();


        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::ADMIN_SHOW)
            ->assertOk()
            ->assertViewIs('admin.view')
            ->assertSeeText(__('user.model_plural'))
            ->assertSeeText(__('public_holiday.model_plural'))
            ->assertSeeText(__('petition_type.model_plural'));
    }

    #[Test]
    public function testAdminPanelRouteCanNotBeVisitedWithoutPermission(): void
    {
        $user = User::factory()
            ->fullyVerified()
            ->create();
        $this->beUser($user)
            ->getByRoute(RouteName::ADMIN_SHOW)
            ->assertForbidden();
    }

    #[Test]
    #[DataProvider('permissionsDataProvider')]
    public function testAdminWithPermissions(Permission $permission, string $translationKey): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::ADMIN_PANEL_VIEW, $permission)
            ->fullyVerified()
            ->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::ADMIN_SHOW)
            ->assertSeeText(__($translationKey));
    }

    #[Test]
    #[DataProvider('permissionsDataProvider')]
    public function testAdminWithoutPermissions(Permission $permission, string $translationKey): void
    {
        $department = Department::factory()->create();

        $user = User::factory()->withPermissionsAndDepartment($department, Permission::ADMIN_PANEL_VIEW)->fullyVerified()->create();

        $this->beUser($user, true, $department)
            ->getByRoute(RouteName::ADMIN_SHOW)
            ->assertDontSeeText(__($translationKey));
    }

    public static function permissionsDataProvider(): array
    {
        return [
            [Permission::USER_WRITE, 'user.model_plural'],
            [Permission::PUBLIC_HOLIDAY_WRITE, 'public_holiday.model_plural'],
            [Permission::PETITION_TYPE_WRITE, 'petition_type.model_plural'],
        ];
    }
}
