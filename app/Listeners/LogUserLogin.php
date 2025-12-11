<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\Login;
use MinVWS\AuditLogger\Events\Logging\UserLoginLogEvent;
use MinVWS\Logging\Laravel\LogService;
use Webmozart\Assert\Assert;

class LogUserLogin
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(Login $event): void
    {
        Assert::isInstanceOf($event->user, User::class);
        $this->logService->log(
            (new UserLoginLogEvent())
                ->asExecute()
                ->withData(['email' => $event->user->email]),
        );
    }
}
