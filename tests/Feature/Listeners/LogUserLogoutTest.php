<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Listeners\LogUserLogout;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogUserLogoutTest extends FeatureTestCase
{
    public function testLogUserLogout(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new Logout('web', $user);

        $listener = new LogUserLogout($logService);
        $listener($event);
    }
}
