<?php

declare(strict_types=1);

namespace App\Models\Casts;

use App\ValueObjects\CalendarDate;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

/**
 * @implements CastsAttributes<CalendarDate, DateTimeInterface|string>
 */
class CalendarDateCast implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CalendarDate
    {
        if ($value === null) {
            return null;
        }

        Assert::string($value);

        return CalendarDate::createFromFormat('Y-m-d', $value);
    }

    /**
     * @param array<string, string> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof CalendarDate) {
            return $value->format('Y-m-d');
        }

        Assert::string($value);

        return $value;
    }
}
