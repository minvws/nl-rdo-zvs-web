<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\OtpAuthenticationSuccessEvent;
use App\Listeners\LogOtpAuthenticationSuccess;
use App\Models\User;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogOtpAuthenticationSuccessTest extends FeatureTestCase
{
    public function testLogOtpAuthenticationSuccess(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $data = ['email' => $user->email, 'action' => 'test'];
        $event = new OtpAuthenticationSuccessEvent($user, $data);

        $listener = new LogOtpAuthenticationSuccess($logService);
        $listener($event);
    }

    public function testLogOtpAuthenticationSuccessWithEmptyData(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $user = User::factory()->make();
        $event = new OtpAuthenticationSuccessEvent($user);

        $listener = new LogOtpAuthenticationSuccess($logService);
        $listener($event);
    }
}
