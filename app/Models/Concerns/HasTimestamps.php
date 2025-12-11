<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasTimestamps
{
    use HasCreatedAt;
    use HasUpdatedAt;

    public function initializeHasTimestamps(): void
    {
        $this->setDateFormat('Y-m-d H:i:sP');
    }
}
