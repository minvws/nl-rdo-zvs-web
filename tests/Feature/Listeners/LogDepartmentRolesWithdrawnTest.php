<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\DepartmentRolesWithdrawnEvent;
use App\Listeners\LogDepartmentRolesWithdrawn;
use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Support\Collection;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogDepartmentRolesWithdrawnTest extends FeatureTestCase
{
    public function testLogDepartmentRolesWithdrawn(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $departmentUser = DepartmentUser::factory()->make();
        $departmentUsers = new Collection([$departmentUser]);
        $event = new DepartmentRolesWithdrawnEvent($user, $departmentUsers);

        $listener = new LogDepartmentRolesWithdrawn($logService);
        $listener($event);
    }

    public function testLogDepartmentRolesWithdrawnWithUser(): void
    {
        $this->actingAs(User::factory()->make());
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $departmentUser = DepartmentUser::factory()->make();
        $departmentUsers = new Collection([$departmentUser]);
        $event = new DepartmentRolesWithdrawnEvent($user, $departmentUsers);

        $listener = new LogDepartmentRolesWithdrawn($logService);
        $listener($event);
    }
}
