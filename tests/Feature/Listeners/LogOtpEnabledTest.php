<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\OtpEnabledEvent;
use App\Listeners\LogOtpEnabled;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogOtpEnabledTest extends FeatureTestCase
{
    public function testLogOtpEnabled(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $data = ['email' => $user->email, 'action' => 'test'];
        $event = new OtpEnabledEvent($user, $data);

        $listener = new LogOtpEnabled($logService);
        $listener($event);
    }

    public function testLogOtpEnabledWithEmptyData(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new OtpEnabledEvent($user);

        $listener = new LogOtpEnabled($logService);
        $listener($event);
    }
}
