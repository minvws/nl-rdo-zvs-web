<?php

declare(strict_types=1);

namespace App\Actions\PasswordResetToken;

use App\Mail\User\PasswordResetMailable;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\HashService;
use App\Services\MailService;
use Carbon\CarbonImmutable;

readonly class ForgotPasswordMailAction
{
    public function __construct(
        private HashService $hashService,
        private MailService $mailService,
    ) {
    }

    public function execute(User $user): PasswordResetToken
    {
        $token = $this->hashService->createToken();

        $passwordResetToken = PasswordResetToken::query()->create([
            'email' => $user->email,
            'token' => $token,
            'created_at' => CarbonImmutable::now(),
        ]);

        $this->mailService->send(new PasswordResetMailable($passwordResetToken->id, $token), $user->email, $user->name);

        return $passwordResetToken;
    }
}
