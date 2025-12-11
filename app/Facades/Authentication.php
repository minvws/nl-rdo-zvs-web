<?php

declare(strict_types=1);

namespace App\Facades;

use App\Models\User;
use App\Services\Authentication\AuthenticationService;
use Illuminate\Support\Facades\Facade;

/**
 * @see AuthenticationService
 *
 * @method static User user()
 */
class Authentication extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AuthenticationService::class;
    }
}
