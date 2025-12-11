<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserCreatedEvent;
use Illuminate\Support\Facades\Auth;
use MinVWS\AuditLogger\Events\Logging\UserCreatedLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogUserCreated
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(UserCreatedEvent $event): void
    {
        $currentUser = Auth::user();

        $logEvent = new UserCreatedLogEvent();
        $logEvent
            ->asCreate()
            ->withTarget($event->user);

        if ($currentUser) {
            $logEvent->withActor($currentUser);
        }
        $this->logService->log($logEvent);
    }
}
