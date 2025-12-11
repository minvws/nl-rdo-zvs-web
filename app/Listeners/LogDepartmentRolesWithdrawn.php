<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DepartmentRolesWithdrawnEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogDepartmentRolesWithdrawn
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(DepartmentRolesWithdrawnEvent $event): void
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
                'department_roles' => $event->departmentUser,
            ])
            ->withTarget($event->user);

        $this->logService->log($logEvent);
    }
}
