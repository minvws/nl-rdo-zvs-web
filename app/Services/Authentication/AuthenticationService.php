<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\User;
use App\Repositories\SessionRepository;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Cache;
use Ramsey\Uuid\UuidInterface;
use Webmozart\Assert\Assert;

use function sprintf;

readonly class AuthenticationService implements AuthenticationServiceInterface
{
    public function __construct(
        private AuthManager $authManager,
        private SessionRepository $sessionRepository,
    ) {
    }

    public function loginAttempt(string $email, string $password, bool $remember): bool
    {
        return $this->authManager->attempt([
            'email' => $email,
            'password' => $password,
            'active' => true,
        ], $remember);
    }

    public function isLoggedIn(): bool
    {
        return $this->authManager->check();
    }

    public function logout(): void
    {
        $this->authManager->logout();
        $this->sessionRepository->regenerate();
    }

    /**
     * @throws AuthenticationException
     */
    public function user(): User
    {
        $authIdentifier = $this->getAuthenticatableIdentifier();

        return Cache::store('array')->remember(
            sprintf('user_%s', $authIdentifier),
            null,
            static function () use ($authIdentifier): User {
                return User::query()->findSole($authIdentifier);
            },
        );
    }

    /**
     * @throws AuthenticationException
     */
    public function logoutEverywhere(): void
    {
        $authIdentifier = $this->getAuthenticatableIdentifier();
        $this->sessionRepository->invalidateUser($authIdentifier);
    }

    /**
     * @throws AuthenticationException
     */
    private function getAuthenticatableIdentifier(): UuidInterface
    {
        $authenticatable = $this->authManager->user();
        if ($authenticatable === null) {
            throw new AuthenticationException('not logged in');
        }

        $authIdentifier = $authenticatable->getAuthIdentifier();
        Assert::isInstanceOf($authIdentifier, UuidInterface::class);

        return $authIdentifier;
    }
}
