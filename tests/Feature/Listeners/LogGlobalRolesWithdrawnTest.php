<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\GlobalRolesWithdrawnEvent;
use App\Listeners\LogGlobalRolesWithdrawn;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogGlobalRolesWithdrawnTest extends FeatureTestCase
{
    public function testLogGlobalRolesWithdrawn(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $roles = ['admin', 'moderator'];
        $event = new GlobalRolesWithdrawnEvent($user, $roles);

        $listener = new LogGlobalRolesWithdrawn($logService);
        $listener($event);
    }

    public function testLogGlobalRolesWithdrawnWithUser(): void
    {
        $this->actingAs(User::factory()->make());
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $roles = ['admin', 'moderator'];
        $event = new GlobalRolesWithdrawnEvent($user, $roles);

        $listener = new LogGlobalRolesWithdrawn($logService);
        $listener($event);
    }
}
