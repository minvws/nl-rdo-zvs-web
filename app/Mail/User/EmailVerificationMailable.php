<?php

declare(strict_types=1);

namespace App\Mail\User;

use App\Mail\Mailable;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

use function __;

class EmailVerificationMailable extends Mailable
{
    use Queueable;
    use SerializesModels;

    public string $link;

    public function __construct(User $user, string $hash)
    {
        $this->link = URL::temporarySignedRoute('verification.verify', CarbonImmutable::now()->addMinutes(60), [
            'user' => $user,
            'hash' => $hash,
        ]);
    }

    public function getSubject(): string
    {
        return __('user.mail.email_verification.subject');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.user.email_verification');
    }
}
