<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpAuthenticationFailedEvent;
use MinVWS\AuditLogger\Events\Logging\UserLoginTwoFactorFailedEvent;
use MinVWS\Logging\Laravel\LogService;

class LogOtpAuthenticationFailed
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(OtpAuthenticationFailedEvent $event): void
    {
        $this->logService->log((new UserLoginTwoFactorFailedEvent())
            ->asExecute()
            ->withActor($event->user)
            ->withData($event->data));
    }
}
