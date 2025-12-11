<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\UserOtpResetEvent;
use App\Listeners\LogUserOtpReset;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogUserOtpResetTest extends FeatureTestCase
{
    public function testLogUserOtpReset(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new UserOtpResetEvent($user);

        $listener = new LogUserOtpReset($logService);
        $listener($event);
    }

    public function testLogUserOtpResetWithActor(): void
    {
        $this->actingAs(User::factory()->make());

        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new UserOtpResetEvent($user);

        $listener = new LogUserOtpReset($logService);
        $listener($event);
    }
}
