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
use function route;

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

    /**
     * The bell in the navigation is also labelled "Notificaties", so the button is recognised by its link.
     *
     * @return array<string, array{Permission, bool}>
     */
    public static function notificationRulesLinkProvider(): array
    {
        return [
            'permitted' => [Permission::NOTIFICATION_RULE_WRITE, true],
            'not permitted' => [Permission::TAG_WRITE, false],
        ];
    }

    #[Test]
    #[DataProvider('notificationRulesLinkProvider')]
    public function testNotificationRulesLink(Permission $permission, bool $expectLink): void
    {
        $department = Department::factory()->create();

        $user = User::factory()
            ->withPermissionsAndDepartment($department, Permission::ADMIN_PANEL_VIEW, $permission)
            ->fullyVerified()
            ->create();

        $link = route(RouteName::DEPARTMENTS_ADMIN_NOTIFICATION_RULES_INDEX, ['department' => $department]);
        $response = $this->beUser($user, true, $department)->getByRoute(RouteName::ADMIN_SHOW);

        $expectLink ? $response->assertSee($link, false) : $response->assertDontSee($link, false);
    }

    public static function permissionsDataProvider(): array
    {
        return [
            [Permission::USER_WRITE, 'user.model_plural'],
            [Permission::PUBLIC_HOLIDAY_WRITE, 'public_holiday.model_plural'],
            [Permission::PETITION_TYPE_WRITE, 'petition_type.model_plural'],
            [Permission::TAG_WRITE, 'tag.model_plural'],
        ];
    }
}
