<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\RouteName;
use App\Mail\Notification\NotificationRuleMailable;
use App\Models\NotificationRule;
use App\Models\Petition;
use App\Models\User;
use App\Services\NotificationRule\NotificationRuleText;
use App\ValueObjects\CalendarDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

use function __;
use function route;

class NotificationRuleTriggered extends Notification implements ShouldQueue
{
    use Queueable;

    public bool $deleteWhenMissingModels = true;

    public function __construct(
        private readonly NotificationRule $notificationRule,
        private readonly Petition $petition,
        private readonly CalendarDate $anchorDate,
        private readonly CalendarDate $triggerDate,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notification_rule.mail.subject', ['number' => $this->petition->number]),
            'regarding' => $this->regarding(),
            'action' => $this->action(),
            'petition_id' => $this->petition->id->toString(),
            'notification_rule_id' => $this->notificationRule->id->toString(),
            'trigger_date' => $this->triggerDate->toDateString(),
            'url' => route(RouteName::DEPARTMENTS_PETITIONS_SHOW, [
                'department' => $this->petition->department,
                'petition' => $this->petition->id,
            ], false),
        ];
    }

    public function toMail(User $notifiable): NotificationRuleMailable
    {
        return new NotificationRuleMailable(
            petitionNumber: $this->petition->number,
            regarding: $this->regarding(),
            action: $this->action(),
        )->to($notifiable->email, $notifiable->name);
    }

    public function databaseType(object $notifiable): string
    {
        return 'notification-rule-triggered';
    }

    private function regarding(): string
    {
        return NotificationRuleText::regarding($this->notificationRule, $this->petition->petitionType->type);
    }

    private function action(): string
    {
        return NotificationRuleText::action($this->notificationRule, $this->anchorDate);
    }
}
