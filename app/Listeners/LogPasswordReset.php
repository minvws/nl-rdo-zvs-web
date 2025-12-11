<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use MinVWS\AuditLogger\Events\Logging\AccountChangeLogEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\Logging\Laravel\LogService;
use Webmozart\Assert\Assert;

class LogPasswordReset
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(PasswordReset $event): void
    {
        Assert::isInstanceOf($event->user, User::class);
        $this->logService->log(
            (new ResetCredentialsLogEvent())
                ->withEventCode(AccountChangeLogEvent::EVENTCODE_RESET)
                ->asExecute()
                ->withData(['email' => $event->user->email]),
        );
    }
}
