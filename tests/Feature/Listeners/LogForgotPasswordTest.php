<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\ForgotPasswordEvent;
use App\Listeners\LogForgotPassword;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogForgotPasswordTest extends FeatureTestCase
{
    public function testLogForgotPassword(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $event = new ForgotPasswordEvent('test@example.com');

        $listener = new LogForgotPassword($logService);
        $listener($event);
    }
}
