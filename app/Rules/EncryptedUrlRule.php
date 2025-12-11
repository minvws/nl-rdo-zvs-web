<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Translation\PotentiallyTranslatedString;
use Throwable;

use function filter_var;
use function is_string;

use const FILTER_VALIDATE_URL;

class EncryptedUrlRule implements ValidationRule
{
    /**
     * @param Closure(string): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        try {
            $decrypted = Crypt::decryptString($value);
            if (!filter_var($decrypted, FILTER_VALIDATE_URL)) {
                $fail('validation.url')->translate();
            }
        } catch (Throwable) {
            $fail('validation.encrypted_string')->translate();
        }
    }
}
