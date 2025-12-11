<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogUserLoginFailed
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(Failed $event): void
    {
        $this->logService->log(
            (new UserLoginLogEvent())
                ->asExecute()
                ->withFailed(true)
                ->withData(['email' => $event->credentials['email']]),
        );
    }
}
