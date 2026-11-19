<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Listeners\LogUserLoginFailed;
use Illuminate\Auth\Events\Failed;
use MinVWS\Logging\Laravel\LogService;
use Mockery;
use Tests\Feature\FeatureTestCase;

class LogUserLoginFailedTest extends FeatureTestCase
{
    public function testLogUserLoginFailed(): void
    {
        $logService = Mockery::mock(LogService::class);
        $logService->expects('log');

        $event = new Failed('web', null, ['email' => 'test@example.com', 'password' => 'wrong']);

        $listener = new LogUserLoginFailed($logService);
        $listener($event);
    }
}
