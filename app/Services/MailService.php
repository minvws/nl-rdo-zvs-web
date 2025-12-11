<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailables\Address;

readonly class MailService
{
    public function __construct(
        private Mailer $mailer,
    ) {
    }

    public function send(Mailable $mailable, string $email, string $name): void
    {
        $this->mailer->to(new Address($email, $name))
            ->queue($mailable);
    }
}
