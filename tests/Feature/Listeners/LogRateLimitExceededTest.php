<?php

declare(strict_types=1);

namespace Tests\Feature\Listeners;

use App\Events\RateLimitExceededEvent;
use App\Listeners\LogRateLimitExceeded;
use MinVWS\Logging\Laravel\LogService;
use Tests\Feature\FeatureTestCase;

class LogRateLimitExceededTest extends FeatureTestCase
{
    public function testLogRateLimitExceeded(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $parameters = ['test@example.com'];
        $ip = '192.168.1.1';
        $event = new RateLimitExceededEvent($parameters, $ip);

        $listener = new LogRateLimitExceeded($logService);
        $listener($event);
    }

    public function testLogRateLimitExceededWithNullIp(): void
    {
        $logService = $this->createMock(LogService::class);
        $logService->expects($this->once())->method('log');

        $parameters = ['test@example.com'];
        $event = new RateLimitExceededEvent($parameters);

        $listener = new LogRateLimitExceeded($logService);
        $listener($event);
    }
}
