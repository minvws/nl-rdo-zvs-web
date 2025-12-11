<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GlobalRolesWithdrawnEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogGlobalRolesWithdrawn
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(GlobalRolesWithdrawnEvent $event): void
    {
        $currentUser = Auth::user();

        $logEvent = new AccountChangeLogEvent();

        if ($currentUser) {
            $logEvent->withActor($currentUser);
        }

        $logEvent
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ROLES)
            ->asDelete()
            ->withData([
                'global_roles' => $event->roles,
            ])
            ->withTarget($event->user);

        $this->logService->log($logEvent);
    }
}
