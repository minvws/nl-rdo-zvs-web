<?php

declare(strict_types=1);

namespace App\Services\NotificationRule;

use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionVariant;
use App\Models\NotificationRule;
use App\ValueObjects\CalendarDate;

use function __;
use function abs;
use function sprintf;
use function trans_choice;

/**
 * The fixed texts of a notification rule, shared by the notification, its mail and the preview on the rule form.
 */
class NotificationRuleText
{
    public static function regarding(NotificationRule $rule, ?PetitionVariant $variant = null): string
    {
        return $rule->event_type->label($variant);
    }

    /**
     * With an anchor date the text tells how far that date is from today, which is not the rule's number of days when
     * the notification goes out late (an event registered afterwards, an assignee set later). Without an anchor date,
     * as in the preview, the text follows the rule and the date is shown as a placeholder.
     */
    public static function action(NotificationRule $rule, ?CalendarDate $anchorDate = null): string
    {
        $offsetDays = $anchorDate instanceof CalendarDate ? $anchorDate->diffInDays(CalendarDate::today()) : $rule->offset_days;
        $timing = NotificationRuleTiming::fromOffset($offsetDays);

        return __(sprintf('notification_rule.action.%s.%s', $rule->anchor->value, $timing->value), [
            'days' => trans_choice('notification_rule.days', abs($offsetDays)),
            'date' => $anchorDate?->format('d-m-Y') ?? __('notification_rule.date_placeholder'),
        ]);
    }

    public static function term(NotificationRule $rule): string
    {
        return $rule->offset_days === 0
            ? __('notification_rule.immediately')
            : trans_choice('notification_rule.days', $rule->numberOfDays());
    }
}
