<?php

declare(strict_types=1);

namespace App\Services\Timeline;

use App\Config\Config;
use App\Enums\TimelineFilterGroup;
use App\Models\TimelineItem;
use Illuminate\Support\Collection;

use function collect;
use function sprintf;

final class TimelineFilterService
{
    /**
     * @param Collection<int, TimelineItem> $timelineItems
     *
     * @return array<string, string>
     */
    public function availableGroupsFor(Collection $timelineItems): array
    {
        $timelineTypesInUse = $timelineItems
            ->map(static fn(TimelineItem $item): string => $item->type->value)
            ->unique()
            ->values();

        return collect(TimelineFilterGroup::sortedByLabel())
            ->filter(static function (string $label, string $group) use ($timelineTypesInUse): bool {
                $groupTypes = Config::array(sprintf('timeline_filters.groups.%s', $group));

                return collect($groupTypes)->intersect($timelineTypesInUse)->isNotEmpty();
            })
            ->all();
    }
}
