<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Casts\DatetimeWithTimezoneCast;
use Carbon\CarbonImmutable;

/**
 * @property CarbonImmutable $updated_at
 */
trait HasUpdatedAt
{
    public function initializeHasUpdatedAt(): static
    {
        return $this->mergeCasts([
            'updated_at' => DatetimeWithTimezoneCast::class,
        ]);
    }
}
