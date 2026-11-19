<?php

declare(strict_types=1);

namespace App\ValueObjects;

use App\Enums\NotificationRuleAnchor;
use App\Enums\PetitionEventType;

readonly class NotificationRuleData
{
    public function __construct(
        public PetitionEventType $eventType,
        public NotificationRuleAnchor $anchor,
        public int $offsetDays,
        public bool $active,
    ) {
    }

    /**
     * @return array{event_type: PetitionEventType, anchor: NotificationRuleAnchor, offset_days: int, active: bool}
     */
    public function toAttributes(): array
    {
        return [
            'event_type' => $this->eventType,
            'anchor' => $this->anchor,
            'offset_days' => $this->offsetDays,
            'active' => $this->active,
        ];
    }
}
