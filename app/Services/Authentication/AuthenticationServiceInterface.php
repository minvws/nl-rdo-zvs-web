<?php

declare(strict_types=1);

namespace App\Services\Authentication;

use App\Models\User;

interface AuthenticationServiceInterface
{
    public function user(): User;

    /**
     * @throws AuthenticationException
     */
    public function logoutEverywhere(): void;

    public function loginAttempt(string $email, string $password, bool $remember): bool;

    public function logout(): void;
}
