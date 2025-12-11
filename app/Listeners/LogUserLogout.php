<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Logout;
use MinVWS\AuditLogger\Events\Logging\UserLogoutLogEvent;
use MinVWS\Logging\Laravel\LogService;
use Webmozart\Assert\Assert;

class LogUserLogout
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(Logout $event): void
    {
        Assert::isInstanceOf($event->user, User::class);
        $this->logService->log(
            (new UserLogoutLogEvent())
                ->asExecute()
                ->withData(['email' => $event->user->email]),
        );
    }
}
