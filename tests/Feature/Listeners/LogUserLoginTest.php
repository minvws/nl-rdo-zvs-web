<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Listeners\LogUserLogin;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogUserLoginTest extends FeatureTestCase
{
    public function testLogUserLogin(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new Login('web', $user, false);

        $listener = new LogUserLogin($logService);
        $listener($event);
    }
}
