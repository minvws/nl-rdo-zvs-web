<?php

declare(strict_types=1);

namespace App\Services\Petition;

use App\Config\Config;
use App\Enums\TimelineFilterGroup;
use App\Models\Petition;
use App\Models\TimelineItem;
use Illuminate\Support\Collection;

use function collect;
use function sprintf;

final readonly class PetitionTimelineService
{
    /**
     * @return Collection<int, TimelineItem>
     */
    public function getFilteredTimelineItems(Petition $petition, ?TimelineFilterGroup $timelineFilterGroup): Collection
    {
        if (!$timelineFilterGroup instanceof TimelineFilterGroup) {
            return $petition->timelineItems;
        }

        $allowedTypes = $this->getAllowedTimelineTypes($timelineFilterGroup);

        return $petition->timelineItems->filter(
            static fn($item) => $allowedTypes->contains($item->type->value),
        );
    }

    /**
     * @return Collection<int, string>
     */
    private function getAllowedTimelineTypes(TimelineFilterGroup $timelineFilterGroup): Collection
    {
        /** @var array<string> $allowedTypes */
        $allowedTypes = Config::array(sprintf('timeline_filters.groups.%s', $timelineFilterGroup->value));

        return collect($allowedTypes);
    }
}
