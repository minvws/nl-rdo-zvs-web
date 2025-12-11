<?php

declare(strict_types=1);

namespace App\Actions\Authentication;

use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\RateLimit\RateLimitExceededException;
use App\Services\RateLimit\RateLimitService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

use function __;

final readonly class LoginAttemptAction
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private RateLimitService $rateLimitService,
    ) {
    }

    /**
     * @throws ValidationException|AuthenticationException
     */
    public function execute(Request $request, string $email, string $password, bool $remember): bool
    {
        try {
            $this->rateLimitService->check($request, $email);
        } catch (RateLimitExceededException) {
            throw ValidationException::withMessages(['email' => __('authentication.ratelimited')]);
        }

        return $this->authenticationService->loginAttempt($email, $password, $remember);
    }
}
