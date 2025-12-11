<?php

declare(strict_types=1);

namespace App\Rules;

use App\Services\Authentication\AuthenticationException;
use App\Services\Authentication\AuthenticationServiceInterface;
use App\Services\HashService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Webmozart\Assert\Assert;

use function __;

readonly class CurrentPassword implements ValidationRule
{
    public function __construct(
        private AuthenticationServiceInterface $authenticationService,
        private HashService $hashService,
    ) {
    }

    /**
     * @param Closure(string): PotentiallyTranslatedString $fail
     *
     * @throws AuthenticationException
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        Assert::string($value);

        $user = $this->authenticationService->user();
        $currentPasswordHash = $user->password;
        Assert::string($currentPasswordHash);

        if (!$this->hashService->verify($value, $currentPasswordHash)) {
            $fail(__('user.validation.current_password_incorrect'));
        }
    }
}
