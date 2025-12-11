<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\DepartmentRolesAssignedEvent;
use App\Listeners\LogDepartmentRolesAssigned;
use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Support\Collection;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogDepartmentRolesAssignedTest extends FeatureTestCase
{
    public function testLogDepartmentRolesAssigned(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $departmentUser = DepartmentUser::factory()->make();
        $departmentUsers = new Collection([$departmentUser]);
        $event = new DepartmentRolesAssignedEvent($user, $departmentUsers);

        $listener = new LogDepartmentRolesAssigned($logService);
        $listener($event);
    }

    public function testLogDepartmentRolesAssignedWithUser(): void
    {
        $this->actingAs(User::factory()->make());
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $departmentUser = DepartmentUser::factory()->make();
        $departmentUsers = new Collection([$departmentUser]);
        $event = new DepartmentRolesAssignedEvent($user, $departmentUsers);

        $listener = new LogDepartmentRolesAssigned($logService);
        $listener($event);
    }
}
