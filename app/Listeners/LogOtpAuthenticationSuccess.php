<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpAuthenticationSuccessEvent;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogOtpAuthenticationSuccess
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(OtpAuthenticationSuccessEvent $event): void
    {
        $this->logService->log((new UserLoginLogEvent())
            ->asExecute()
            ->withActor($event->user)
            ->withData($event->data));
    }
}
