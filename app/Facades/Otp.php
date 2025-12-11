<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\Authentication\OneTimePasswordService;
use Illuminate\Support\Facades\Facade;

/**
 * @see OneTimePasswordService
 *
 * @method static string|null getOtpSecret(object $user)
 * @method static string|null generateQRCodeInline(object $user)
 * @method static void disable(object $user)
 * @method static void enable(object $user)
 * @method static bool hasOtpVerified(object $user)
 * @method static bool hasOtpEnabled(object $user)
 * @method static bool hasValidSession()
 * @method static bool verifyCode(string $code, object $user)
 */
class Otp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return OneTimePasswordService::class;
    }
}
