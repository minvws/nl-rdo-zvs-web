<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\OtpEnrollmentConfirmedEvent;
use App\Listeners\LogOtpEnrollmentConfirmed;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogOtpEnrollmentConfirmedTest extends FeatureTestCase
{
    public function testLogOtpEnrollmentConfirmed(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $data = ['email' => $user->email, 'action' => 'test'];
        $event = new OtpEnrollmentConfirmedEvent($user, $data);

        $listener = new LogOtpEnrollmentConfirmed($logService);
        $listener($event);
    }

    public function testLogOtpEnrollmentConfirmedWithEmptyData(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $user = User::factory()->make();
        $event = new OtpEnrollmentConfirmedEvent($user);

        $listener = new LogOtpEnrollmentConfirmed($logService);
        $listener($event);
    }
}
