<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Listeners\LogPasswordReset;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogPasswordResetTest extends FeatureTestCase
{
    public function testLogPasswordReset(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new PasswordReset($user);

        $listener = new LogPasswordReset($logService);
        $listener($event);
    }
}
