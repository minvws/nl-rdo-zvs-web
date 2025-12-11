<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\OtpDisabledEvent;
use App\Listeners\LogOtpDisabled;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogOtpDisabledTest extends FeatureTestCase
{
    public function testLogOtpDisabled(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $data = ['email' => $user->email];
        $event = new OtpDisabledEvent($user, $data);

        $listener = new LogOtpDisabled($logService);
        $listener($event);
    }

    public function testLogOtpDisabledWithEmptyData(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new OtpDisabledEvent($user);

        $listener = new LogOtpDisabled($logService);
        $listener($event);
    }
}
