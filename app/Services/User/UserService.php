<?php

declare(strict_types=1);

namespace App\Services\User;

use App\Jobs\Mail\EmailVerification;
use App\Models\User;
use App\Services\HashService;
use Carbon\CarbonImmutable;
use Ramsey\Uuid\UuidInterface;

use function dispatch;

/**
 * @phpstan-type UserServiceObjectShape object{
 *      id: UuidInterface,
 *      name: string,
 *      email: string,
 *      email_verified_at: ?CarbonImmutable,
 *      otp_verified_at: ?CarbonImmutable,
 *      otp_secret: ?string,
 *      remember_token: ?string,
 *      password: ?string,
 * }
 */
readonly class UserService
{
    public function __construct(
        private HashService $hashService,
    ) {
    }

    public function hasVerifiedEmail(User $user): bool
    {
        return $user->email_verified_at !== null;
    }

    public function sendEmailVerificationMail(User $user): void
    {
        dispatch(new EmailVerification($user, $this->createEmailVerificationHash($user)));
    }

    /**
     * @throws UserException
     */
    public function verifyEmailByHash(User $user, string $hash): void
    {
        $hashCheckResult = $this->verifyEmailVerificationHash($user, $hash);
        if ($hashCheckResult === false) {
            throw new UserException('invalid hash');
        }

        $user->update(['email_verified_at' => CarbonImmutable::now()]);
    }

    private function createEmailVerificationHash(User $user): string
    {
        return $this->hashService->hash($user->email);
    }

    private function verifyEmailVerificationHash(User $user, string $hash): bool
    {
        return $this->hashService->verify($user->email, $hash);
    }
}
