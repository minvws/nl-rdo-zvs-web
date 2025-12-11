<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\DepartmentUser;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

readonly class DepartmentRolesWithdrawnEvent
{
    use Dispatchable;

    public function __construct(
        public User $user,
        /** @var Collection<int, DepartmentUser> */
        public Collection $departmentUser,
    ) {
    }
}
