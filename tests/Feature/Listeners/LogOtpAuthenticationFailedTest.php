<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\OtpAuthenticationFailedEvent;
use App\Listeners\LogOtpAuthenticationFailed;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogOtpAuthenticationFailedTest extends FeatureTestCase
{
    public function testLogOtpAuthenticationFailed(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $data = ['email' => $user->email, 'action' => 'test'];
        $event = new OtpAuthenticationFailedEvent($user, $data);

        $listener = new LogOtpAuthenticationFailed($logService);
        $listener($event);
    }

    public function testLogOtpAuthenticationFailedWithEmptyData(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $event = new OtpAuthenticationFailedEvent($user);

        $listener = new LogOtpAuthenticationFailed($logService);
        $listener($event);
    }
}
