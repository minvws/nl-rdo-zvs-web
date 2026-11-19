<?php

declare(strict_types=1);

namespace App\Services\NotificationRule;

use App\Enums\NotificationRuleRecipient;
use App\Models\NotificationRule;
use App\Models\NotificationRuleDelivery;
use App\Models\Petition;
use App\Models\User;
use App\Notifications\NotificationRuleTriggered;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;

/**
 * Sends the notifications of a petition's department rules whose trigger date has been reached.
 *
 * A rule only fires for trigger dates from the day it became active up to today, so it never sends notifications
 * for the past. Each notification is recorded before it is sent; when that record already exists the notification
 * went out before, so saving events and the daily job never send the same notification twice.
 */
class NotificationRuleEvaluator
{
    public function __construct(
        private readonly NotificationRuleAnchorResolver $anchorResolver,
        private readonly DatabaseManager $databaseManager,
    ) {
    }

    public function evaluate(Petition $petition): void
    {
        if ($petition->archived_at !== null) {
            return;
        }

        $rules = NotificationRule::query()
            ->forDepartment($petition->department)
            ->active()
            ->get();

        foreach ($rules as $rule) {
            $recipient = $this->recipient($rule, $petition);
            if (!$recipient instanceof User) {
                continue;
            }

            foreach ($this->anchorResolver->anchorDates($rule, $petition) as $anchorDate) {
                $this->notifyWhenDue($rule, $petition, $recipient, $anchorDate);
            }
        }
    }

    /**
     * A deactivated user gets nothing, and nothing is recorded, so the notification still goes out to whoever is
     * assigned next.
     */
    private function recipient(NotificationRule $rule, Petition $petition): ?User
    {
        $recipient = match ($rule->recipient) {
            NotificationRuleRecipient::PRIMARY_ASSIGNEE => $petition->firstAssignee?->user,
        };

        return $recipient?->active === true ? $recipient : null;
    }

    private function notifyWhenDue(NotificationRule $rule, Petition $petition, User $recipient, CalendarDate $anchorDate): void
    {
        $triggerDate = $anchorDate->addDays($rule->offset_days);

        if ($triggerDate->isBefore($rule->active_from) || $triggerDate->greaterThan(CalendarDate::today())) {
            return;
        }

        // The record and the queued notification are committed together, so a notification that fails to queue is
        // tried again next time instead of being marked as sent
        $this->databaseManager->transaction(static function () use ($rule, $petition, $recipient, $anchorDate, $triggerDate): void {
            $recorded = NotificationRuleDelivery::query()->insertOrIgnore([
                'id' => Uuid::uuid4()->toString(),
                'notification_rule_id' => $rule->id->toString(),
                'petition_id' => $petition->id->toString(),
                'anchor_date' => $anchorDate->toDateString(),
                'trigger_date' => $triggerDate->toDateString(),
                'created_at' => CarbonImmutable::now(),
            ]);

            if ($recorded === 0) {
                return;
            }

            $recipient->notify(new NotificationRuleTriggered($rule, $petition, $anchorDate, $triggerDate));
        });
    }
}
