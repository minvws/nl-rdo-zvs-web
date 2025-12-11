<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\EloquentModel;
use App\Models\TimelineItem;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface TimelineableInterface
{
    /**
     * @return MorphMany<TimelineItem, EloquentModel&$this>
     */
    public function timelineItems(): MorphMany;
}
