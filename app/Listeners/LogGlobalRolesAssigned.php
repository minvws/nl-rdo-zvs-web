<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\GlobalRolesAssignedEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogGlobalRolesAssigned
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(GlobalRolesAssignedEvent $event): void
    {
        $currentUser = Auth::user();

        $logEvent = new AccountChangeLogEvent();

        if ($currentUser) {
            $logEvent->withActor($currentUser);
        }

        $logEvent
            ->withEventCode(AccountChangeLogEvent::EVENTCODE_ROLES)
            ->asCreate()
            ->withData([
                'global_roles' => $event->roles,
            ])
            ->withTarget($event->user);

        $this->logService->log($logEvent);
    }
}
