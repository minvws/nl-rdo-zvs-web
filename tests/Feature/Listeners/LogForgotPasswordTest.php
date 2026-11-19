<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\ForgotPasswordEvent;
use App\Listeners\LogForgotPassword;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogForgotPasswordTest extends FeatureTestCase
{
    public function testLogForgotPassword(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $event = new ForgotPasswordEvent('test@example.com');

        $listener = new LogForgotPassword($logService);
        $listener($event);
    }
}
