<?php

declare(strict_types=1);

namespace App\Services\Authentication\OneTimePassword;

use Override;

class FakeOneTimePassword extends TimedOneTimePassword
{
    #[Override]
    public function isCodeValid(string $code, string $secret): bool
    {
        return true;
    }
}
