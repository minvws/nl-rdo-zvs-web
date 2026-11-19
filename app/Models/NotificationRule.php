<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\NotificationRuleAnchor;
use App\Enums\NotificationRuleRecipient;
use App\Enums\NotificationRuleTiming;
use App\Enums\PetitionEventType;
use App\Models\Casts\CalendarDateCast;
use App\Models\Concerns\HasDepartment;
use App\Models\Concerns\HasId;
use App\Models\Concerns\HasTimestamps;
use App\Models\Contracts\DepartmentAwareInterface;
use App\QueryBuilders\NotificationRuleQueryBuilder;
use App\ValueObjects\CalendarDate;
use Database\Factories\NotificationRuleFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Override;

use function abs;

/**
 * @property NotificationRuleAnchor $anchor
 * @property PetitionEventType $event_type
 * @property int $offset_days Calendar days from the anchor date: negative is before it, 0 is on it, positive is after it
 * @property NotificationRuleRecipient $recipient
 * @property bool $active
 * @property CalendarDate $active_from
 *
 * @property-read Collection<int, NotificationRuleDelivery> $deliveries
 */
#[Table('notification_rules')]
#[UseEloquentBuilder(NotificationRuleQueryBuilder::class)]
#[UseFactory(NotificationRuleFactory::class)]
class NotificationRule extends EloquentModel implements DepartmentAwareInterface
{
    use HasDepartment;
    /** @use HasFactory<NotificationRuleFactory> */
    use HasFactory;
    use HasId;
    use HasTimestamps;

    public function timing(): NotificationRuleTiming
    {
        return NotificationRuleTiming::fromOffset($this->offset_days);
    }

    public function numberOfDays(): int
    {
        return abs($this->offset_days);
    }

    /**
     * @return HasMany<NotificationRuleDelivery, $this>
     */
    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationRuleDelivery::class);
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'anchor' => NotificationRuleAnchor::class,
            'event_type' => PetitionEventType::class,
            'offset_days' => 'integer',
            'recipient' => NotificationRuleRecipient::class,
            'active' => 'boolean',
            'active_from' => CalendarDateCast::class,
        ];
    }
}
