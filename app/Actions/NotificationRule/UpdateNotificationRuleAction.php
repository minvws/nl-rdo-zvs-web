<?php

declare(strict_types=1);

namespace App\Actions\NotificationRule;

use App\Models\NotificationRule;
use App\ValueObjects\CalendarDate;
use App\ValueObjects\NotificationRuleData;

class UpdateNotificationRuleAction
{
    /**
     * A rule that starts counting differently, or is switched back on, only sends notifications from today on, so
     * the change never sends notifications for the past.
     */
    public function execute(NotificationRule $notificationRule, NotificationRuleData $data): NotificationRule
    {
        $wasActive = $notificationRule->active;
        $notificationRule->fill($data->toAttributes());

        if ($notificationRule->isDirty(['event_type', 'anchor', 'offset_days']) || (!$wasActive && $notificationRule->active)) {
            $notificationRule->active_from = CalendarDate::today();
        }

        $notificationRule->save();

        return $notificationRule;
    }
}
