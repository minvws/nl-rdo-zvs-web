<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Http\Request;
use Webmozart\Assert\Assert;

readonly class FormHelper
{
    public function __construct(
        private Request $request,
    ) {
    }

    public function old(string $key, ?string $default = null): ?string
    {
        $oldValue = $this->request->old($key, $default);
        Assert::nullOrString($oldValue);

        return $oldValue ?? $default;
    }

    /**
     * @template T of array<string, mixed>|null
     *
     * @param T $default
     *
     * @return (T is array ? array<string, mixed> : array<string, mixed>|null)
     */
    public function oldArray(string $key, ?array $default = null): array|null
    {
        $oldValue = $this->request->old($key, $default);
        if ($oldValue === null) {
            return null;
        }

        Assert::isArray($oldValue);

        return $oldValue;
    }
}
