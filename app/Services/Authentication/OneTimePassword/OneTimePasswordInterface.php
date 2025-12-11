<?php

declare(strict_types=1);

namespace App\Services\Authentication\OneTimePassword;

interface OneTimePasswordInterface
{
    public function isCodeValid(string $code, string $secret): bool;

    public function generateSecretKey(): string;

    public function generateQRCodeInline(string $label, string $secret): string;
}
