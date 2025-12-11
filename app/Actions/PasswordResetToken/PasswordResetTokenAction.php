<?php

declare(strict_types=1);

namespace App\Actions\PasswordResetToken;

use App\Models\PasswordResetToken;
use App\Services\HashService;
use Carbon\CarbonImmutable;

readonly class PasswordResetTokenAction
{
    public function __construct(
        private HashService $hashService,
    ) {
    }

    public function execute(string $email, ?string $token = null): PasswordResetToken
    {
        PasswordResetToken::query()->where('email', $email)
            ->delete();

        return PasswordResetToken::query()->create([
            'email' => $email,
            'token' => $token ?? $this->createToken(),
            'created_at' => CarbonImmutable::now(),
        ]);
    }

    private function createToken(): string
    {
        return $this->hashService->createToken();
    }
}
