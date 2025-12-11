<?php

declare(strict_types=1);

namespace App\Services;

class ValidationResult
{
    /**
     * @param array<string, string> $errors
     */
    public function __construct(
        protected array $errors = [],
    ) {
    }

    public function isValid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return array<string, string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
