<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OtpEnrollmentConfirmedEvent;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogOtpEnrollmentConfirmed
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(OtpEnrollmentConfirmedEvent $event): void
    {
        $this->logService->log((new AccountChangeLogEvent())
            ->asUpdate()
            ->withActor($event->user)
            ->withTarget($event->user)
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_RESET)
            ->withData($event->data));
    }
}
