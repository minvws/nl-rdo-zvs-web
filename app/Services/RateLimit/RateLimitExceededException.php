<?php

declare(strict_types=1);

namespace App\Services\RateLimit;

use App\Exception\AppException;

class RateLimitExceededException extends AppException
{
}
