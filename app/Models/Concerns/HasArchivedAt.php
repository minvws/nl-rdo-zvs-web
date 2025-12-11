<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Casts\DatetimeWithTimezoneCast;
use Carbon\CarbonImmutable;

/**
 * @property ?CarbonImmutable $archived_at
 */
trait HasArchivedAt
{
    public function initializeHasArchivedAt(): static
    {
        return $this->mergeCasts([
            'archived_at' => DatetimeWithTimezoneCast::class,
        ]);
    }
}
