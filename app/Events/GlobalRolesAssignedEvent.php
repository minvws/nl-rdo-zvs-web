<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

readonly class GlobalRolesAssignedEvent
{
    use Dispatchable;

    public function __construct(
        public User $user,
        /**
         * @var array<string> $roles
         */
        public array $roles,
    ) {
    }
}
