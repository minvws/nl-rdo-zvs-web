<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\RateLimitExceededEvent;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogRateLimitExceeded
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(RateLimitExceededEvent $event): void
    {
        $this->logService->log((new UserLoginLogEvent())
            ->asExecute()
            ->withData([
                'rate_limit_exceeded' => true,
                'parameters' => $event->parameters,
                'ip' => $event->ip,
            ]));
    }
}
