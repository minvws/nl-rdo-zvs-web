<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ForgotPasswordEvent;
use MinVWS\AuditLogger\Events\Logging\ResetCredentialsLogEvent;
use MinVWS\Logging\Laravel\LogService;

class LogForgotPassword
{
    public function __construct(private readonly LogService $logService)
    {
    }

    public function __invoke(ForgotPasswordEvent $event): void
    {
        $this->logService->log(
            (new ResetCredentialsLogEvent())
                ->asExecute()
                ->withData([
                    'reason' => 'forgot_password',
                    'email' => $event->email,
                ])
                ->logFullRequest(true),
        );
    }
}
