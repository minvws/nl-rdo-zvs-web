<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserOtpResetEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogUserOtpReset
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(UserOtpResetEvent $event): void
    {
        $currentUser = Auth::user();

        $logEvent = new ResetCredentialsLogEvent();
        $logEvent
            ->asUpdate()
            ->withTarget($event->user);

        if ($currentUser) {
            $logEvent->withActor($currentUser);
        }

        $logEvent
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_RESET)
            ->asExecute()
            ->withTarget($event->user);

        $this->logService->log($logEvent);
    }
}
