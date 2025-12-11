<?php

declare(strict_types=1);

namespace App\Mail\User;

use App\Mail\Mailable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Ramsey\Uuid\UuidInterface;

use function __;

class PasswordResetMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $link;

    public function __construct(UuidInterface $id, string $token)
    {
        $this->link = URL::route('password.reset.request', [
            'id' => $id->toString(),
            'token' => $token,
        ]);
    }

    public function getSubject(): string
    {
        return __('user.mail.password_reset.subject');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.user.password_reset');
    }
}
