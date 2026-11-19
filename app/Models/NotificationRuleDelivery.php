<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasCreatedAt;
use App\Models\Concerns\HasId;
use App\ValueObjects\CalendarDate;
use Carbon\CarbonImmutable;
use Database\Factories\NotificationRuleDeliveryFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * Records that a notification rule fired for a petition on a trigger date, so it is sent only once.
 *
 * @property UuidInterface $notification_rule_id
 * @property UuidInterface $petition_id
 * @property CalendarDate $anchor_date
 * @property CalendarDate $trigger_date
 * @property CarbonImmutable $created_at
 *
 * @property-read NotificationRule $notificationRule
 * @property-read Petition $petition
 */
#[Table('notification_rule_deliveries')]
#[UseFactory(NotificationRuleDeliveryFactory::class)]
class NotificationRuleDelivery extends EloquentModel
{
    use HasCreatedAt;
    /** @use HasFactory<NotificationRuleDeliveryFactory> */
    use HasFactory;
    use HasId;

    public const UPDATED_AT = null;

    /**
     * @return BelongsTo<NotificationRule, $this>
     */
    public function notificationRule(): BelongsTo
    {
        return $this->belongsTo(NotificationRule::class);
    }

    /**
     * @return BelongsTo<Petition, $this>
     */
    public function petition(): BelongsTo
    {
        return $this->belongsTo(Petition::class);
    }

    #[Override]
    protected function casts(): array
    {
        return [
            'anchor_date' => CalendarDateCast::class,
            'trigger_date' => CalendarDateCast::class,
        ];
    }
}
