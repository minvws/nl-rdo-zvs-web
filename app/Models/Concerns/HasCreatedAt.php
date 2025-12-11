<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Casts\DatetimeWithTimezoneCast;
use Carbon\CarbonImmutable;

/**
 * @property CarbonImmutable $created_at
 */
trait HasCreatedAt
{
    public function initializeHasCreatedAt(): static
    {
        return $this->mergeCasts([
            'created_at' => DatetimeWithTimezoneCast::class,
        ]);
    }
}
