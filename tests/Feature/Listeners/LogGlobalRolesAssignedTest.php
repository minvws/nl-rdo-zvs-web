<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\GlobalRolesAssignedEvent;
use App\Listeners\LogGlobalRolesAssigned;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogGlobalRolesAssignedTest extends FeatureTestCase
{
    public function testLogGlobalRolesAssigned(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $roles = ['admin', 'moderator'];
        $event = new GlobalRolesAssignedEvent($user, $roles);

        $listener = new LogGlobalRolesAssigned($logService);
        $listener($event);
    }

    public function testLogGlobalRolesAssignedWithUser(): void
    {
        $this->actingAs(User::factory()->make());
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $roles = ['admin', 'moderator'];
        $event = new GlobalRolesAssignedEvent($user, $roles);

        $listener = new LogGlobalRolesAssigned($logService);
        $listener($event);
    }
}
