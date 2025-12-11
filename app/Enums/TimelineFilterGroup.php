<?php

declare(strict_types=1);

namespace App\Enums;

use function __;
use function collect;
use function sprintf;

enum TimelineFilterGroup: string
{
    case UPDATES = 'updates';
    case ATTACHMENTS = 'attachments';
    case NOTES = 'notes';
    case STATUS_CHANGES = 'status_changes';
    case TERM_ADJUSTMENTS = 'term_adjustments';
    case ASSIGNMENTS = 'assignments';

    public function label(): string
    {
        return __(sprintf('timeline.filter_groups.%s', $this->value));
    }

    /**
     * @return array<string, string>
     */
    public static function sortedByLabel(): array
    {
        return collect(self::cases())
            ->mapWithKeys(static fn($group): array => [$group->value => $group->label()])
            ->sort()
            ->all();
    }
}
