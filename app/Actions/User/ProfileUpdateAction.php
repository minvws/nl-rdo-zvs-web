<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;

class ProfileUpdateAction
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function execute(User $user, array $attributes): void
    {
        $user->update($attributes);
    }
}
