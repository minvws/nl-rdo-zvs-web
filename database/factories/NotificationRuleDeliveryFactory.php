<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationRule;
use App\Models\NotificationRuleDelivery;
use App\Models\Petition;
use App\ValueObjects\CalendarDate;

/**
 * @extends Factory<NotificationRuleDelivery>
 */
class NotificationRuleDeliveryFactory extends Factory
{
    /** @var class-string<NotificationRuleDelivery> $model */
    protected $model = NotificationRuleDelivery::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'notification_rule_id' => NotificationRule::factory(),
            'petition_id' => Petition::factory(),
            'anchor_date' => CalendarDate::today(),
            'trigger_date' => CalendarDate::today(),
        ];
    }
}
