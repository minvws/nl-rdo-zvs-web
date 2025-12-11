<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpDisabledEvent;
use MinVWS\AuditLogger\Events\Logging\VerificationCodeDisabledLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogOtpDisabled
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(OtpDisabledEvent $event): void
    {
        $this->logService->log((new VerificationCodeDisabledLogEvent())
            ->asExecute()
            ->withActor($event->user)
            ->withTarget($event->user)
            ->withData($event->data));
    }
}
