<?php

declare(strict_types=1);

namespace App\Jobs\Mail;

use App\Mail\User\EmailVerificationMailable;
use App\Models\User;
use App\Services\MailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EmailVerification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly User $user,
        private readonly string $hash,
    ) {
    }

    public function handle(MailService $mailService): void
    {
        $mailable = new EmailVerificationMailable($this->user, $this->hash);

        $mailService->send($mailable, $this->user->email, $this->user->name);
    }
}
