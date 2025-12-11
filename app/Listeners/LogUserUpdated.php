<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserUpdatedEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogUserUpdated
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(UserUpdatedEvent $event): void
    {
        $currentUser = Auth::user();

        $logEvent = new AccountChangeLogEvent();
        $logEvent
            ->asUpdate()
            ->withTarget($event->user);

        if ($currentUser) {
            $logEvent->withActor($currentUser);
        }
        $this->logService->log($logEvent);
    }
}
