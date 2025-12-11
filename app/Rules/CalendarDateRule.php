<?php

declare(strict_types=1);

namespace App\Rules;

use App\ValueObjects\CalendarDate;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use InvalidArgumentException;

use function is_string;

class CalendarDateRule implements ValidationRule
{
    /**
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        try {
            CalendarDate::createFromFormat(CalendarDate::DEFAULT_STRING_FORMAT, $value);
        } catch (InvalidArgumentException) {
            $fail('validation.calendar_date')->translate();
        }
    }
}
