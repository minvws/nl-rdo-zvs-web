<?php

declare(strict_types=1);

namespace App\Services\RateLimit;

use App\Events\RateLimitExceededEvent;
use Illuminate\Cache\RateLimiter;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function event;

readonly class RateLimitService
{
    public function __construct(
        private RateLimiter $rateLimiter,
        #[Config('app.rate_limit.max_attempts')]
        private int $maxAttempts,
        #[Config('app.rate_limit.decay_seconds')]
        private int $decaySeconds,
    ) {
    }

    /**
     * @throws RateLimitExceededException
     */
    public function check(Request $request, string ...$parameters): void
    {
        $keyParts = new Collection($parameters);
        $keyParts->add($request->getRequestUri());

        $ip = $request->ip();
        if ($ip !== null) {
            $keyParts->add($ip);
        }

        $key = Str::transliterate($keyParts->join('|'));

        if ($this->rateLimiter->tooManyAttempts($key, $this->maxAttempts)) {
            event(new RateLimitExceededEvent($parameters, $ip));

            throw new RateLimitExceededException();
        }

        $this->rateLimiter->hit($key, $this->decaySeconds);
    }
}
