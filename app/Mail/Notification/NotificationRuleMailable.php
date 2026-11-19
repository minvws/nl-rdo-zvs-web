<?php

declare(strict_types=1);

namespace App\Mail\Notification;

use App\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;

use function __;

class NotificationRuleMailable extends Mailable
{
    public function __construct(
        public readonly string $petitionNumber,
        public readonly string $regarding,
        public readonly string $action,
    ) {
    }

    public function getSubject(): string
    {
        return __('notification_rule.mail.subject', ['number' => $this->petitionNumber]);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.notification.notification_rule');
    }
}
