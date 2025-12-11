<?php

declare(strict_types=1);

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;

class RateLimitExceededEvent
{
    use Dispatchable;

    public function __construct(
        /** @var array<int|string, string> */
        public array $parameters,
        public ?string $ip = null,
    ) {
    }
}
