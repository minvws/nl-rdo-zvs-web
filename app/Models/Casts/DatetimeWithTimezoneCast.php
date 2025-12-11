<?php

declare(strict_types=1);

namespace App\Models\Casts;

use App\Config\Config;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Webmozart\Assert\Assert;

/**
 * @implements CastsAttributes<CarbonInterface, DateTimeInterface|string>
 */
class DatetimeWithTimezoneCast implements CastsAttributes
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        Assert::string($value);
        $date = CarbonImmutable::parse($value);

        return $date->setTimezone(Config::string('app.timezone'));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:sP');
        }

        return $value;
    }
}
