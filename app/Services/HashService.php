<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

use function hash_hmac;

readonly class HashService
{
    public function __construct(
        private Hasher $hasher,
        #[Config('app.key')]
        private string $key,
    ) {
    }

    public function createToken(): string
    {
        return hash_hmac('sha256', Str::random(40), $this->key);
    }

    public function hash(string $value): string
    {
        return $this->hasher->make($value);
    }

    public function verify(string $value, string $hash): bool
    {
        return $this->hasher->check($value, $hash);
    }
}
