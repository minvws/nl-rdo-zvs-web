<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Events\UserOtpResetEvent;
use App\Models\User;

use function event;

readonly class UserOtpResetAction
{
    public function execute(User $user): void
    {
        $user->update(
            [
                'otp_secret' => null,
                'otp_verified_at' => null,
            ],
        );

        event(new UserOtpResetEvent($user));
    }
}
