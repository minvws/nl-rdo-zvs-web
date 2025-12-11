<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class OtpEnrollmentConfirmedEvent
{
    use Dispatchable;

    public function __construct(
        public User $user,
        /** @var array<string, mixed> */
        public array $data = [],
    ) {
    }
}
