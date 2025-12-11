<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Encryption\Encrypter;
use Webmozart\Assert\Assert;

readonly class EncryptionService
{
    public function __construct(
        private Encrypter $encrypter,
    ) {
    }

    public function decrypt(string $payload): string
    {
        $value = $this->encrypter->decrypt($payload);
        Assert::string($value);

        return $value;
    }

    public function encrypt(string $value): string
    {
        return $this->encrypter->encrypt($value);
    }
}
