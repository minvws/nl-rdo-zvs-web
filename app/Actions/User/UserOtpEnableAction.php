<?php

declare(strict_types=1);

namespace App\Actions\User;

use App\Models\User;
use App\Services\Authentication\OneTimePassword\OneTimePasswordInterface;
use App\Services\EncryptionService;

class UserOtpEnableAction
{
    public function __construct(
        private readonly EncryptionService $encryptionService,
        private readonly OneTimePasswordInterface $oneTimePassword,
    ) {
    }

    public function execute(User $user): void
    {
        $encryptedOtpSecret = $this->encryptionService->encrypt($this->oneTimePassword->generateSecretKey());

        $user->update(['otp_secret' => $encryptedOtpSecret]);
    }
}
